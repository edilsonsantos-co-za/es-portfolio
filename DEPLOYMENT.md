# Optimized Deployment System

## Overview

This project uses an optimized deployment system that intelligently handles deployments based on what actually changed, minimizing downtime and resource usage.

## Key Features

### 🚀 Smart Change Detection
- **Docker Rebuild**: Only rebuilds images when infrastructure files change (Dockerfile, composer.json, package.json, etc.)
- **Database Changes**: Only runs migrations/seeding when database-related files change
- **Code-Only Updates**: Fast updates for PHP/JS code changes without container rebuilds

### 🗃️ Intelligent Database Management
- **Auto Migration**: Runs migrations only when pending migrations exist
- **Smart Seeding**: Seeds database only when critical tables are empty
- **Backup Creation**: Automatic database backups before changes

### ⚡ Performance Optimizations
- **Layer Caching**: Docker build uses optimized layer caching
- **Code Injection**: For code-only changes, injects new code into running containers
- **Graceful Restarts**: PHP/Apache restarts without full container recreation

## Deployment Types

### 1. Full Rebuild (Infrastructure Changes)
Triggered when these files change:
- `Dockerfile`
- `composer.json` / `composer.lock`
- `package.json` / `package-lock.json`
- `.dockerignore`

**Process:**
1. Stop existing containers
2. Build new Docker image with commit SHA tag
3. Start new containers
4. Run health checks

### 2. Fast Code Update (Code-Only Changes)
Triggered when only application code changes.

**Process:**
1. Inject new code into running container
2. Clear PHP caches
3. Run migrations if database files changed
4. Graceful Apache restart

### 3. Documentation-Only (Skip Deployment)
When only `.md` or documentation files change, deployment is skipped entirely.

## Database Management

### Migration Behavior
- `RUN_MIGRATIONS=auto` (default): Only run if pending migrations exist
- `RUN_MIGRATIONS=true`: Always run migrations
- `RUN_MIGRATIONS=false`: Never run migrations

### Seeding Behavior  
- `RUN_SEEDING=auto` (default): Only seed if critical tables are empty
- `RUN_SEEDING=true`: Always run seeding
- `RUN_SEEDING=false`: Never run seeding

## Manual Deployment Controls

### GitHub Actions Manual Triggers

You can manually trigger deployments with custom options:

1. Go to GitHub Actions → Deploy to VPS (Optimized)
2. Click "Run workflow"
3. Choose options:
   - **Force Docker rebuild**: Forces full image rebuild regardless of changes
   - **Force migrations/seeding**: Forces database operations regardless of detection
   - **Project name**: Override project name (default: edfolio)

### Environment Variables

Set these in your server environment to control behavior:

```bash
# Database control
export RUN_MIGRATIONS=auto    # auto|true|false  
export RUN_SEEDING=auto       # auto|true|false
export FORCE_FRESH_DB=false   # true to wipe and recreate database

# Build control
export SKIP_ASSET_BUILD=true  # production assets are compiled during docker build
export CREATE_DB_BACKUP=true  # true to backup database before changes
```

## Container Management

### Image Tagging
- Images are tagged with commit SHA: `edfolio:a1b2c3d4`
- Latest image is always tagged as `edfolio:latest`
- Old images are kept (last 3) for rollback capability

### Container Names
- Production: `edfolio-prod`
- Development: `edfolio-dev`

## Health Checks

The deployment system includes robust health checks:

1. **Container Status**: Verifies container is running
2. **HTTP Response**: Tests application responds to requests
3. **HTTPS Verification**: Checks if HTTPS is working correctly
4. **Retry Logic**: 6 attempts with 10-second intervals
5. **Failure Logging**: Container logs on failure

## Network Configuration

- Uses Docker network: `milkyway`
- Automatically created if doesn't exist
- Connects to external database container

## File Structure

```
.github/workflows/
├── deploy.yml              # Optimized deployment workflow
└── deploy-legacy.yml.bak   # Backup of old workflow

.env.dev                     # Development environment
.env.prod                    # Production environment  
bin/start-apache.sh              # Smart container startup script
bin/create-env.sh                # Environment setup script
```

## Deployment Flow

```mermaid
graph TD
    A[Push to main] --> B[detect-changes job]
    B --> C{Code changes?}
    C -->|No| D[Skip deployment]
    C -->|Yes| E{Docker rebuild needed?}
    E -->|Yes| F[Full rebuild]
    E -->|No| G[Fast code update]
    F --> H[Health check]
    G --> H[Health check]
    H --> I[Deployment complete]
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

### Rollback
If needed, rollback to previous image:
```bash
# List available images
docker images edfolio

# Start with previous image
docker run -d --name edfolio-prod-rollback edfolio:PREVIOUS_SHA
```

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
- The Docker Hub image (`datasparklab/edfolio:<tag>`) already contains `public/js/*` and `public/mix-manifest.json`.
- Deployments only need `docker compose pull` and `docker compose up -d`, which avoids the previous VPS memory pressure during asset compilation.
- If the image build fails in GitHub Actions, deployment stops before the VPS is touched.

## GitHub Actions Deployment Pipeline

Production deployments now follow this order:

1. The single `Validate and Deploy` workflow starts on push, pull request, or manual dispatch.
2. The `validate` job runs first.
3. On `main`, the `build-and-publish` job waits for validation, then publishes the Docker image to Docker Hub.
4. The `deploy-production` job waits for the image push, then SSHes to the VPS, pulls the new image, and restarts the production service.

### Required GitHub Secrets

Configure these secrets before enabling the deploy workflow:

```bash
DOCKERHUB_USERNAME=your-docker-hub-login-username
DOCKERHUB_TOKEN=your-docker-hub-access-token
```

Recommended split:

- **Repository or organization secrets**
  - `DOCKERHUB_USERNAME`
  - `DOCKERHUB_TOKEN`
- **`production` environment secrets**
  - `DEPLOY_HOST`
  - `DEPLOY_USER`
  - `DEPLOY_PATH`
  - `DEPLOY_SSH_PRIVATE_KEY`

Important:

- `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN` are required by the `build-and-publish` job before the workflow reaches the `production` environment, so they must be configured as repository or organization secrets.
- `DOCKERHUB_USERNAME` is used to authenticate to Docker Hub. The workflow publishes images to the fixed `datasparklab` organization namespace, so the login account must have permission to push to `datasparklab/edfolio`.
- The `production` environment must exist before you add `DEPLOY_*` environment secrets. If it is missing, create it first in GitHub and then load the deploy secrets.

Example environment secret values:

```bash
DEPLOY_HOST=your-vps-hostname-or-ip
DEPLOY_USER=your-ssh-user
DEPLOY_PATH=/workspace/edilsonsantos-co-za/edfolio
DEPLOY_SSH_PRIVATE_KEY=-----BEGIN OPENSSH PRIVATE KEY-----
```

### Bootstrap Secrets with GitHub CLI

You can set the required secrets for this repository with:

```bash
./bin/setup-github-secrets.sh edilsonsantos-co-za/edfolio \
  --dockerhub-username YOUR_DOCKERHUB_USERNAME \
  --dockerhub-token YOUR_DOCKERHUB_TOKEN \
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
APP_IMAGE_TAG=latest
```

The deploy workflow sets these automatically on the VPS for each release so the server always starts the exact image GitHub just published.

The image name is derived in the deploy job from the fixed `datasparklab` namespace and the repository name instead of being passed as a job output, because GitHub Actions suppresses secret-like outputs between jobs.

During deployment, GitHub Actions writes a temporary Compose override on the VPS that pins the `app` service to the exact `${APP_IMAGE}:${APP_IMAGE_TAG}` being released. This avoids stale server-side `.env` or Compose defaults accidentally pulling an old image name such as `edfolio`.

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
