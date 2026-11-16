# Deployment

Every push to `main` goes live. `.github/workflows/ci-deploy.yml` runs the validation job, then `deploy`:

1. Builds the frontend assets (`public/build`) on the runner.
2. Ships the committed files plus `public/build` over SSH to `/workspace/edilsonsantos-co-za/es-portfolio`
   on the VPS (or under the `DEPLOY_PATH` secret). Env files are never shipped: the server's `.env.prod` is
   the one that counts, and `bin/server-env.sh` only fills in what is missing (SSO link, mail).
3. Runs `deploy-k3s.sh` there (workspace `config/kubernetes/scripts`): builds `es-portfolio:<commit>` on the
   server (no image registry), turns `.env.prod` into the Secret `es-portfolio-env`, applies `k8s/`, checks
   `/ready` through the cluster and goes back to the previous version if the new one does not become ready.
4. `notify-google-chat` posts the result when `GOOGLE_CHAT_WEBHOOK_URL` is set.

`workflow_dispatch` re-runs a deploy from GitHub Actions without a new commit.

## Production stack: Kubernetes (since 17 Nov 2026)

The site runs as one pod on the k3s cluster on the VPS. Nginx Proxy Manager still terminates HTTPS and forwards
`edilsonsantos.co.za` to Traefik, the cluster's entry point.

| What | Value |
| --- | --- |
| Manifests | `k8s/` (`deployment.yaml`, `service.yaml`, `ingress.yaml`), namespace `apps` |
| Copies | One, replaced on each deploy (`strategy: Recreate`): a pause of under a minute |
| Settings | Secret `es-portfolio-env`, rebuilt from the server's `.env.prod` on every deploy |
| Health checks | `/ready` (start-up and readiness: needs the database), `/health` (liveness) |
| Uploads (`MEDIA_DISK=public`) | Docker volume `edfolio_edfolio_storage`, mounted at `storage/app/public` |
| Installed themes | Docker volume `edfolio_edfolio_themes`, mounted at `storage/app/themes` |
| Database backups | Docker volume `edfolio_prod_backups`, mounted at `storage/app/backups` (a dump on every start) |

**Why one copy.** Sign-in sessions and the cache are files inside the container, and `bin/start-apache.sh` takes a
database backup and runs migrations when it starts. Two copies would each have their own sessions and would both
migrate. The alternative, moving sessions and cache to the database and running two copies as ES Blog does, removes
the pause on deploy but is a code change; it is the next step if the pause matters.

**Why the same folders.** The pod mounts the folders the Docker container used (`hostPath` to the three Docker
volumes). No data was copied, the nightly backups cover them as before, and going back to Docker is one command.
The cost: the app is tied to this server until uploads move to S3 (below).

**How it reaches MySQL, Redis, sign-in and mail.** Those still run in Docker. The cluster bridge (workspace
`config/kubernetes/bridge`) gives the pod the same names `.env.prod` already uses (`dsl-mysql`, `dsl-redis`,
`dsl-sso`, `usesend-smtp-server`).

**Failure modes.**

- A build that never answers `/ready` within six minutes: the script rolls back and the deploy fails. Because the
  old copy was stopped first, the site is down until the previous version is back up.
- A migration that fails: `start-apache.sh` logs it and continues, so check the pod log after a schema change.
- `${NAME}` in `.env.prod` is replaced with that key's value from the same file, as Docker Compose does
  (`MAIL_FROM_NAME="${APP_NAME}"`). A literal `$` in a double-quoted value must be written `\$`.
- The visitor's IP address arrives in `X-Forwarded-For` only because Traefik trusts the proxy
  (workspace `config/kubernetes/platform/vps-test/traefik.yaml`). If every visitor shows as `172.30.0.1`, that
  setting was lost.

**Commands now run in the pod:** `k3s kubectl -n apps exec deploy/es-portfolio -- php artisan <command>`;
logs: `k3s kubectl -n apps logs deploy/es-portfolio`.

**Back to Docker.** On the server, in the app folder: `docker compose -f docker-compose.prod.yml up -d --wait`,
then `python3 /usr/local/lib/workspace/npm-route.py edilsonsantos.co.za --to docker:es-portfolio:80`, then
`k3s kubectl -n apps scale deployment/es-portfolio --replicas=0`. `docker-compose.prod.yml` is kept for this and
for servers without a cluster. The volume names date from the first deploys (compose project `edfolio`); never
rename them.

**Verify.** `k3s kubectl -n apps get pods -l app=es-portfolio` shows one pod `1/1 Running`, and
`curl -s https://edilsonsantos.co.za/ready` returns `"status":"ready"`. This does not prove an admin sign-in or
an upload: check those in the browser.

**Try it.** Predict what a visitor sees if the pod is deleted, then run
`k3s kubectl -n apps delete pod -l app=es-portfolio --wait=false` and `curl -s -o /dev/null -w '%{http_code}\n'
https://edilsonsantos.co.za/` in a loop until it answers `200` again. Compare with ES Blog, which runs two copies.

## Secrets

Environment `production`: `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_PRIVATE_KEY`, plus the optional
`DEPLOY_KNOWN_HOSTS` and `DEPLOY_PATH` (this app's folder or the workspace root). Mail: `SMTP_HOST`,
`SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `MAIL_FROM_ADDRESS`. Until those are set, mail goes to the
Mailtrap sandbox (`MAILTRAP_USERNAME`, `MAILTRAP_PASSWORD`).

## Versions and rollback

The root `VERSION` file is the application version; `release.yml` checks that pushed `vX.Y.Z` tags match it.
To roll back, revert the commit on `main` and push: the deploy rebuilds that state. To return at once to the
previous image without a build: `k3s kubectl -n apps rollout undo deployment/es-portfolio` on the server. Follow the safety rules in
[UPGRADE_PATH.md](UPGRADE_PATH.md) for schema changes (additive migrations, backups before deploying).

## S3 media storage

Set `MEDIA_DISK=s3` in `.env.prod` to keep uploads in S3-compatible storage instead of the volume:

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

Older database values such as `storage/avatar/...` keep working: when the local file is gone but the same
object exists in S3, the S3 URL is used.

## Debugging

```bash
docker ps --filter name=es-portfolio
docker logs es-portfolio --tail=50 -f
docker exec -it es-portfolio bash
docker exec es-portfolio tail -f /var/www/html/storage/logs/laravel.log
docker exec es-portfolio php artisan config:clear
curl -f https://edilsonsantos.co.za/
```
