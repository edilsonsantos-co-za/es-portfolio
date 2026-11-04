# Development

Local development runs one container, `es-portfolio-dev` (`docker-compose.dev.yml`), with the repository
bind-mounted into `/var/www/html`. The database is the shared `core-database-config-mysql` container on the
external `milkyway` network, and Nginx Proxy Manager serves the app at `edilsonsantos.local`. No host ports
are published.

```bash
cp .env.dev.example .env.dev        # then fill in the secrets
docker compose -f docker-compose.dev.yml up -d --build
```

On start the container builds the assets, runs pending migrations and seeds an empty database
(`RUN_MIGRATIONS=auto`, `RUN_SEEDING=auto`). `FORCE_FRESH_DB=true` drops and reseeds the shared dev database.

```bash
docker exec -it es-portfolio-dev bash
docker exec es-portfolio-dev php artisan migrate
docker exec es-portfolio-dev php artisan cache:clear
docker exec es-portfolio-dev npm run dev
docker exec es-portfolio-dev ./bin/validate.sh
docker logs es-portfolio-dev --tail=100
```

The container also answers on its old name on `milkyway`, so a local proxy host that still forwards there keeps
working; point it at `es-portfolio-dev:80` and drop the alias from `docker-compose.dev.yml`.

See [README.md](README.md) for the application itself and [DEPLOYMENT.md](DEPLOYMENT.md) for production.
