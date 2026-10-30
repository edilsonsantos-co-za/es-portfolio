# Deployment System

## Push to main = live (current)

Every push to `main` runs `.github/workflows/ci-deploy.yml`: validation, then the `deploy` job builds
the frontend assets on the runner, ships the committed files plus `public/build` over SSH to
`/workspace/edilsonsantos-co-za/edfolio` (or the `DEPLOY_PATH` secret) and runs
`docker compose -f docker-compose.prod.yml up -d --build --wait` there. No image registry is involved,
and env files are never shipped: `.env.prod` on the server is the one that counts.

Secrets in the `production` environment: `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_PRIVATE_KEY`
(optional `DEPLOY_KNOWN_HOSTS`, `DEPLOY_PATH`). The sections below describe the older image-based flow.

## Overview

Production deployment is now image-based and GitHub Actions-driven:

1. `validate` runs the repository quality gates.
2. `build-and-publish` builds frontend assets on the runner, packages them into the Docker image, and pushes the image to Docker Hub.
3. `deploy-production` SSHes to the VPS, generates the current production Compose file under the deployment path, pulls the requested image tag, and recreates `edfolio-prod`.
4. `notify-google-chat` posts a non-blocking deployment status message when `GOOGLE_CHAT_WEBHOOK_URL` is configured.

This replaces the older "smart code injection / fast code update" approach. The current system always deploys immutable images.

## Current Workflow Files

```text
.github/workflows/
├── ci-deploy.yml   # validate, build, deploy, notify
└── release.yml     # validates pushed vX.Y.Z tags against VERSION
```

## Current Deployment Characteristics

- Docker images are published to `datasparklab/edfolio`
- Release images use the root `VERSION` file and are tagged as `vX.Y.Z`
- The most recent build is also tagged as `latest`
- The production container name is `edfolio-prod`
- The production Docker network is `milkyway`
- Frontend assets are built in CI and baked into the image
- Deployments verify that `edfolio-prod` is actually running after `docker compose up -d`

For framework, frontend, or database upgrades, also follow the production safety rules in [`UPGRADE_PATH.md`](UPGRADE_PATH.md): use additive migrations, pre-deploy backups, smoke tests, image-tag rollback, and avoid destructive schema changes until a later cleanup release.

## Manual Deploy Trigger

The `CI and Deploy` workflow supports `workflow_dispatch`, so you can re-run a deploy manually from GitHub Actions without pushing a new commit. The workflow file is `.github/workflows/ci-deploy.yml`.

## Release Model

The root `VERSION` file is the single source of truth for application and image versioning.

- `VERSION` stores the raw semantic version, for example `0.0.9`
- The application exposes both `0.0.9` and `v0.0.9` to Blade views
- Docker images are published as `datasparklab/edfolio:v0.0.9`
- The latest successful build is also published as `datasparklab/edfolio:latest`
- The production deploy job pulls the release tag form (`vX.Y.Z`)

Push an annotated Git tag such as `v0.0.9` to run `.github/workflows/release.yml`, which verifies the tag matches `VERSION`.

## Production Runtime Expectations

The current production container expects:

```bash
SKIP_ASSET_BUILD=true
FORCE_FRESH_DB=false
CREATE_DB_BACKUP=true
REQUIRE_DB_ON_BOOT=true
```

The application can force HTTPS URL generation, but only when the public hostname is actually backed by a valid certificate at the reverse proxy.

Recommended `.env.prod` pattern:

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://edilsonsantos.co.za
FORCE_HTTPS=true
ASSET_URL=
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=*
```

Production database settings must also point to a hostname that resolves from inside the app container on the `milkyway` network. If the shared MySQL service name changes or the container is not attached to that network, Laravel will serve partial pages and log `php_network_getaddresses` / `getaddrinfo` failures until the runtime configuration is corrected.

The runtime bootstrap now treats database reachability as a hard production requirement by default. If the app cannot connect to `DB_HOST` during startup, the container exits instead of serving a partially broken admin UI.

If the public host does not yet have a valid certificate, temporarily use:

```bash
APP_URL=http://edilsonsantos.co.za
FORCE_HTTPS=false
SESSION_SECURE_COOKIE=false
```

Then clear and rebuild config inside the container:

```bash
docker exec edfolio-prod php artisan config:clear
docker exec edfolio-prod php artisan config:cache
docker restart edfolio-prod
```

## Rollback Strategy

Rollbacks should use a previously published release tag rather than an old commit SHA.

Typical process:

1. Choose a known-good tag such as `v0.0.8`
2. Pull that image on the VPS
3. Recreate the production service with that tag

Example:

```bash
docker pull datasparklab/edfolio:v0.0.8
```

Then use the same generated Compose pattern as the deploy workflow, replacing `APP_IMAGE_TAG` with the chosen release tag.

## Container Management

### Image Tagging
- Images are tagged from the root `VERSION` file as `datasparklab/edfolio:v0.0.9`
- Latest image is also tagged as `datasparklab/edfolio:latest`
- Old images are kept (last 3) for rollback capability

### Container Names
- Production: `edfolio-prod`
- Development: `edfolio-dev`

## Health Checks

The deployment system includes deployment verification and runtime health checks:

1. **Container Status**: Verifies container is running
2. **Container Healthcheck**: Production Compose defines an HTTP healthcheck against `http://localhost`
3. **Failure Logging**: Deploy failures print container status and recent logs

## Network Configuration

- Uses external Docker network: `milkyway`
- The network must already exist on the VPS before deployment
- Connects the app container to shared production services on that network

## File Structure

```text
.github/workflows/
├── ci-deploy.yml   # validate, build, deploy, notify
└── release.yml     # validates pushed vX.Y.Z tags against VERSION

.env.dev              # Development environment template
.env.prod             # Production environment values on the VPS
docker-compose.prod.yml
bin/start-apache.sh   # Runtime bootstrap inside the app image
bin/create-env.sh     # Local environment helper
```

## Deployment Flow

```mermaid
graph TD
    A[Push or manual dispatch] --> B[validate]
    B --> C[build-and-publish]
    C --> D[deploy-production]
    D --> E[notify-google-chat]
```

## Monitoring & Debugging

### Deployment Logs
- GitHub Actions provides detailed deployment logs
- Container startup logs available via `docker logs edfolio-prod`

### Health Check Failures
If deployment fails:
1. Check GitHub Actions logs for specific error
2. SSH into server and check container status: `docker ps`
3. View container logs: `docker logs edfolio-prod --tail=50`
4. Check application logs: `docker exec edfolio-prod tail -f /var/www/html/storage/logs/laravel.log`

## Persistent Storage for Uploaded Files

Uploaded media can be stored either on the local `public` disk (`storage/app/public`) or on S3, depending on `MEDIA_DISK`.

### `edfolio_storage` Named Volume

A Docker named volume (`edfolio_storage`) is mounted at `/var/www/html/storage/app/public` in the production container. This is used when `MEDIA_DISK=public` so uploaded files survive container restarts and redeploys.

- **First-time setup**: The volume is created automatically by Docker Compose on first `up`. No manual action needed.
- **The storage symlink** (`public/storage → storage/app/public`) is created on startup only if it doesn't already exist, so container restarts with a populated volume are safe.
- **Repository uploads are isolated**: production no longer bind-mounts the whole repository into `/var/www/html`, so `repo:upload` rsync operations do not mutate the filesystem of the running container before a rebuild.

### Frontend Assets in Production

Production frontend assets are now baked into the Docker image during CI. The VPS no longer compiles JavaScript during deployment.

- Keep `SKIP_ASSET_BUILD=true` in production so the app container expects precompiled assets from the image.
- The Docker Hub image (`datasparklab/edfolio:<tag>`) already contains the Vite output in `public/build/`, including `public/build/manifest.json`.
- Deployments only need `docker compose pull` and `docker compose up -d`, which avoids the previous VPS memory pressure during asset compilation.
- If the image build fails in GitHub Actions, deployment stops before the VPS is touched.

## GitHub Actions Deployment Pipeline

Production deployments now follow this order:

1. The single `Validate and Deploy` workflow starts on push, pull request, or manual dispatch.
2. The `validate` job runs first.
3. On `main`, the `build-and-publish` job waits for validation, then publishes the Docker image to Docker Hub.
4. The `deploy-production` job waits for the image push, then SSHes to the VPS, pulls the new image, and restarts the production service.
5. A final `notify-google-chat` job reports the deployment result to Google Chat when a webhook secret is configured.

### Version Source of Truth

The root `VERSION` file is the single source of truth for application and image versioning.

- `VERSION` stores the raw semantic version, for example `0.0.9`
- The application exposes both `0.0.9` and `v0.0.9` to Blade views
- The build and deploy workflow uses the release tag form (`v0.0.9`) for Docker image publishing and production pulls

### Required GitHub Secrets

Configure these secrets before enabling the deploy workflow:

```bash
DOCKERHUB_USERNAME=your-docker-hub-login-username
DOCKERHUB_TOKEN=your-docker-hub-access-token
GOOGLE_CHAT_WEBHOOK_URL=https://chat.googleapis.com/...
```

Recommended split:

- **Repository or organization secrets**
  - `DOCKERHUB_USERNAME`
  - `DOCKERHUB_TOKEN`
  - `GOOGLE_CHAT_WEBHOOK_URL` (optional)
- **`production` environment secrets**
  - `DEPLOY_HOST`
  - `DEPLOY_USER`
  - `DEPLOY_PATH`
  - `DEPLOY_SSH_PRIVATE_KEY`

Important:

- `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN` are required by the `build-and-publish` job before the workflow reaches the `production` environment, so they must be configured as repository or organization secrets.
- `DOCKERHUB_USERNAME` is used to authenticate to Docker Hub. The workflow publishes images to the fixed `datasparklab` organization namespace, so the login account must have permission to push to `datasparklab/edfolio`.
- `GOOGLE_CHAT_WEBHOOK_URL` is optional. When set as a repository or organization secret, the final workflow job posts a Google Chat message with the deployment status, repository, branch, author, commit, workflow run link, and a summary of changed files.
- The `production` environment must exist before you add `DEPLOY_*` environment secrets. If it is missing, create it first in GitHub and then load the deploy secrets.

Example environment secret values:

```bash
DEPLOY_HOST=your-vps-hostname-or-ip
DEPLOY_USER=your-ssh-user
DEPLOY_PATH=/workspace/edilsonsantos-co-za/edfolio
DEPLOY_SSH_PRIVATE_KEY=-----BEGIN OPENSSH PRIVATE KEY-----
```

Example production application settings in `.env.prod`:

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=http://edilsonsantos.co.za
FORCE_HTTPS=false
```

Only switch `APP_URL` to `https://...` and set `FORCE_HTTPS=true` after the public hostname is backed by a valid certificate at the reverse proxy/load balancer. Until then, leaving `FORCE_HTTPS=false` prevents Laravel from rewriting asset URLs to a broken HTTPS origin.

### Bootstrap Secrets with GitHub CLI

You can set the required secrets for this repository with:

```bash
./bin/setup-github-secrets.sh edilsonsantos-co-za/edfolio \
  --dockerhub-username YOUR_DOCKERHUB_USERNAME \
  --dockerhub-token YOUR_DOCKERHUB_TOKEN \
  --google-chat-webhook-url YOUR_GOOGLE_CHAT_WEBHOOK_URL \
  --deploy-host YOUR_VPS_HOST \
  --deploy-user YOUR_VPS_USER \
  --deploy-path /workspace/edilsonsantos-co-za/edfolio \
  --deploy-key-file ~/.ssh/your_deploy_key \
  --environment production
```

To roll this pattern out across many repositories, loop over `gh repo list` output and call the same script per repository, or promote shared values like Docker Hub credentials to organization secrets.

### Production Compose Image Settings

`docker-compose.prod.yml` now reads the application image from environment variables:

```bash
APP_IMAGE=datasparklab/edfolio
APP_IMAGE_TAG=v0.0.9
```

The deploy workflow sets these automatically on the VPS for each release so the server always starts the exact image GitHub just published.

The image name is derived in the deploy job from the fixed `datasparklab` namespace and the repository name instead of being passed as a job output, because GitHub Actions suppresses secret-like outputs between jobs.

During deployment, GitHub Actions writes a temporary Compose file under `/tmp` on the VPS using the current production service definition plus the exact `${APP_IMAGE}:${APP_IMAGE_TAG}` being released. This avoids stale server-side Compose files or defaults accidentally pulling an old image name or obsolete services such as the former `assets` container, and it does not require the deploy user to have write access inside `DEPLOY_PATH`.

After `docker compose up -d`, the workflow verifies that the expected `edfolio-prod` container is running. If the container is missing, the deploy job now fails with container status and recent logs.

When `GOOGLE_CHAT_WEBHOOK_URL` is configured, the workflow posts a non-blocking notification after the deploy attempt. Notification delivery issues do not fail the deployment pipeline.

### Release Flow

Use the following release flow to keep Docker image tags, application UI versioning, and Git tags aligned:

1. Update `VERSION` to the next semantic version.
2. Commit the release with `chore(release): vX.Y.Z`.
3. Create an annotated Git tag with `git tag -a vX.Y.Z -m "vX.Y.Z"`.
4. Push the commit and tag.
5. The `Release Validation` workflow runs on `v*.*.*` tags and fails if `VERSION` does not match the pushed tag exactly.

Using Git tags for releases is preferred over bare version-only commit subjects because tags create an auditable release marker without losing meaningful commit history between releases.

### S3 Media Storage

Set `MEDIA_DISK=s3` in `.env.prod` to store uploaded images and files in S3-compatible object storage instead of the local volume.

Required variables:

```bash
MEDIA_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=...
AWS_BUCKET=...
AWS_URL=
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Existing legacy database values like `storage/avatar/...` remain backward compatible. If the local file is gone but the same relative object exists in S3, the application will resolve the S3 URL automatically.

### Migrating from an Old Deployment

If you previously stored uploaded files directly in `assets/common/img/` (outside the storage volume), copy them into the named volume before redeploying or upload them to S3 using the same relative folder layout:

```bash
# Copy existing files into the running container's storage volume
docker cp assets/common/img/. edfolio-prod:/var/www/html/storage/app/public/

# Fix ownership
docker exec edfolio-prod chown -R www-data:www-data /var/www/html/storage/app/public
```

## Best Practices

1. **Test changes locally** before pushing to main
2. **Use feature branches** for major changes
3. **Monitor deployments** via GitHub Actions
4. **Keep database backups** (automatically created)
5. **Use manual deployment triggers** for emergency deployments

## Troubleshooting

### Common Issues

1. **Health check failures**: Usually indicates application startup issues
2. **Migration failures**: Check database connectivity and permissions  
3. **Asset build failures**: Often memory-related, handled gracefully
4. **Container startup timeouts**: Increase timeout or check resource constraints

### Debug Commands

```bash
# Check container status
docker ps | grep edfolio

# View recent logs  
docker logs edfolio-prod --tail=50 -f

# Execute commands in container
docker exec -it edfolio-prod bash

# Check application health
curl -f https://edilsonsantos.co.za/
```
