# Deployment

Every push to `main` goes live. `.github/workflows/ci-deploy.yml` runs the validation job, then `deploy`:

1. Builds the frontend assets (`public/build`) on the runner.
2. Ships the committed files plus `public/build` over SSH to `/workspace/edilsonsantos-co-za/es-portfolio`
   on the VPS (or under the `DEPLOY_PATH` secret). Env files are never shipped: the server's `.env.prod` is
   the one that counts, and `bin/server-env.sh` only fills in what is missing (SSO link, mail).
3. Runs `docker compose -f docker-compose.prod.yml up -d --build --wait` there. The image is built on the
   server; there is no image registry.
4. `notify-google-chat` posts the result when `GOOGLE_CHAT_WEBHOOK_URL` is set.

`workflow_dispatch` re-runs a deploy from GitHub Actions without a new commit.

## Production stack

| What | Value |
| --- | --- |
| Compose project | `es-portfolio` (`docker-compose.prod.yml`) |
| Container / image | `es-portfolio` / `es-portfolio:prod`, on the external `milkyway` network |
| Health check | `curl -f http://localhost/ready` inside the container |
| Uploads (`MEDIA_DISK=public`) | volume mounted at `storage/app/public` |
| Installed themes | volume mounted at `storage/app/themes` |
| Database backups | volume mounted at `storage/app/backups` (a dump is taken on every start) |

The three data volumes keep the names the first deploys gave them, so a rename never starts from empty
volumes. Before replacing an older container, the deploy checks that those volumes exist and stops if one is
missing, leaving the running site alone.

### Rename from the old stack

The first deploy after the rename moves the old server folder to `es-portfolio` (leaving a symlink at the old
path), builds the new image, removes the old container and starts `es-portfolio`. The new container also
answers on the old container name on `milkyway`, so the Nginx Proxy Manager host keeps working. Once
the proxy host forwards to `es-portfolio:80`, drop that alias from `docker-compose.prod.yml`.

## Secrets

Environment `production`: `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_PRIVATE_KEY`, plus the optional
`DEPLOY_KNOWN_HOSTS` and `DEPLOY_PATH` (this app's folder or the workspace root). Mail: `SMTP_HOST`,
`SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `MAIL_FROM_ADDRESS`. Until those are set, mail goes to the
Mailtrap sandbox (`MAILTRAP_USERNAME`, `MAILTRAP_PASSWORD`).

## Versions and rollback

The root `VERSION` file is the application version; `release.yml` checks that pushed `vX.Y.Z` tags match it.
To roll back, revert the commit on `main` and push: the deploy rebuilds that state. Follow the safety rules in
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
