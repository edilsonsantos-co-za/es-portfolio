# EdFolio

A self-hosted developer portfolio with a content admin panel, built with Laravel 12 and React 19.

> EdFolio is a maintained fork of [ezfolio](https://github.com/arifszn/ezfolio) by [Ariful Alam (arifszn)](https://github.com/arifszn).
> The original gave it its shape: a Laravel API, a React admin and Blade portfolio themes. This fork brings the stack
> up to date, hardens it for production and adds a new design and single sign-on. Credit for the original design and
> idea goes to the upstream project.

## What this fork adds

**A current stack, upgraded step by step**
- Laravel 12 on PHP 8.2, reached through Laravel 10 and 11 one release at a time
- React 19, Ant Design 6, React Router 7 and Redux 5; Laravel Mix replaced by Vite
- Node 20 toolchain, ESLint and pinned dependency audits

**Production hardening**
- Docker images for development and production, with health and readiness endpoints
- Operational routes locked down (the bundled log viewer is removed), native CORS middleware
- Database readiness probe, additive indexes, JSON shadow columns, and production data that is never reseeded on container restart
- A pre-push validation hook (lint, build, compose checks) and feature tests for the hardened routes

**One design: Atlas**
- The portfolio, the admin panel and its sign-in page share one look: a bento grid with a live local clock, an
  availability tile and stack meters on the public page, with light and dark modes and `prefers-reduced-motion` support
- Other themes (the original Azure, Rigel, Vega and Procyon, and the Orion family) moved to the separate ES
  Marketplace repository. The public site links to the store with `STORE_URL`.
- Themes install from Admin → Portfolio → Themes: pick one from the store or upload a downloaded package. No
  unzipping, copying or code. Packages must be signed by a key in `config/theme-keys/` (every file is checked
  against the signed manifest) and land in `storage/app/themes/<id>/`, a volume in production so installs survive
  redeploys. An unknown or removed theme falls back to Atlas. `THEME_STORE_URL` sets the store;
  `THEMES_ALLOW_UNSIGNED=true` allows unsigned packages (not recommended: Blade views are code).

**Single sign-on (optional)**
- Admin sign-in through [Logto](https://logto.io) using OpenID Connect (authorization code + PKCE), with no extra packages
- Only existing admins can sign in: the verified email must match an admin account. Accounts are never created automatically.
- With SSO configured, `/admin/login` goes straight to the SSO sign-in page and signing out also ends the SSO session.
  The password form is only shown on installs without SSO.
- Enabled by setting `LOGTO_ENDPOINT`, `LOGTO_APP_ID` and `LOGTO_APP_SECRET`. Password login keeps working alongside it.

## Features

- Projects, experience, education, skills and services, all managed from the admin panel
- Contact form with an inbox in the admin, and visitor analytics
- Admin accent colour, logo and favicon set from the settings screen
- Blog link in the navigation when `BLOG_URL` is set
- Responsive, mobile-first layouts

## Tech stack

| Layer | Tools |
|---|---|
| Backend | Laravel 12, PHP 8.2, MySQL, JWT (tymon/jwt-auth) |
| Admin | React 19, Ant Design 6, Redux 5, React Router 7, Vite |
| Portfolio | Blade themes with plain CSS and JavaScript (no build step) |
| Runtime | Docker, Apache |

## Local Development

### Prerequisites
- Docker Desktop or Docker Engine
- Docker Compose
- Git

### Quick Start

1. **Clone the repository**
   ```bash
   git clone https://github.com/edilsonsantos/edfolio.git
   cd edfolio
   ```

2. **Set up development environment**
   ```bash
   ./bin/create-env.sh dev
   ```

3. **Start the Docker containers**
   ```bash
   docker-compose -f docker-compose.dev.yml up -d
   ```

4. **Access the application**
   - **Portfolio**: http://localhost:8080
   - **Admin Panel**: http://localhost:8080/admin
   - **phpMyAdmin**: http://localhost:8081
   - **MailHog**: http://localhost:8025

5. **Default admin credentials**
   - Email: `admin@admin.com`
   - Password: `12345`

   **Important:** Change these credentials immediately after first login!

### What Happens on First Start

The development environment automatically:
- Creates and configures MySQL database
- Runs database migrations
- Seeds initial data (admin user, sample content)
- Installs PHP dependencies via Composer
- Installs Node dependencies via npm
- Builds frontend assets
- Configures Redis for caching

## Environment Configuration

The project uses a simplified two-environment setup:

### Environment Files

- `.env.dev` - Development environment template
- `.env.prod` - Production environment template
- `.env` - Active environment (created by `create-env.sh`)

### Switching Environments

```bash
# Set up development environment
./bin/create-env.sh dev

# Set up production environment
./bin/create-env.sh prod
```

The `create-env.sh` script automatically:
- Copies the appropriate template to `.env`
- Creates a backup of any existing `.env` file
- Clears Laravel's configuration cache

### Environment Variables Reference

Key configuration options:

```env
# Application
APP_ENV=local|production
APP_DEBUG=true|false
APP_URL=http://localhost:8080
FORCE_HTTPS=false

# Database behavior
RUN_MIGRATIONS=auto|true|false      # Control migration execution
RUN_SEEDING=auto|true|false         # Control database seeding
FORCE_FRESH_DB=false                # Wipe database on startup
CREATE_DB_BACKUP=true               # Backup before migrations

# Build behavior
SKIP_ASSET_BUILD=false              # Skip frontend asset building

# Optional integrations
BLOG_URL=                           # Adds a Blog link to the public site
STORE_URL=                          # Store link; defaults to https://store.edilsonsantos.co.za when unset, empty hides it
PORTFOLIO_AVAILABILITY=             # Atlas availability tile text, e.g. "Open to remote contract work"; empty hides the tile
PORTFOLIO_TIMEZONE=UTC              # Timezone for the Atlas local-time clock, e.g. Africa/Johannesburg
PORTFOLIO_TIME_NOTE=                # Short note under the clock, e.g. "overlaps UK and EU"
LOGTO_ENDPOINT=                     # e.g. https://auth.example.com (enables SSO with the two below)
LOGTO_APP_ID=
LOGTO_APP_SECRET=
LOGTO_INTERNAL_ENDPOINT=            # Optional server-to-server URL, e.g. http://logto:3001 in Docker
```

To set up SSO, create a **Traditional web** application in Logto with the redirect URI
`<APP_URL>/auth/sso/callback` and the post sign-out redirect URI `<APP_URL>/`.

Set `FORCE_HTTPS=true` only when the deployed hostname is actually terminated with a valid HTTPS certificate and your proxy forwards the usual `X-Forwarded-*` headers. Leaving it `false` prevents Laravel from rewriting every asset URL to `https://` on hosts that are still serving plain HTTP or have incomplete TLS setup.

## Common Development Tasks

### Validation and local safeguards

Use the Node 20 LTS baseline before running npm-based validation. This matches
CI/Docker and keeps frontend builds reproducible:

```bash
nvm install
nvm use
```

Enable the repository git hooks once per clone:

```bash
./bin/setup-git-hooks.sh
```

Run the standard local validation suite before pushing or deploy planning:

```bash
./bin/validate.sh
```

That validation path is also what the repository pre-push hook runs once you enable hooks with `./bin/setup-git-hooks.sh`. It currently covers Docker Compose config checks, PHPUnit, ESLint for `resources/js`, and the frontend production build.

What it currently checks:
- `docker compose -f docker-compose.dev.yml config` or `docker-compose -f docker-compose.dev.yml config`
- `docker compose -f docker-compose.prod.yml config` or `docker-compose -f docker-compose.prod.yml config`
- `./vendor/bin/phpunit`
- `npm run lint`
- `npm run build`

If Docker Compose is not on `PATH`, the script warns and skips the Compose config check. Set `VALIDATE_REQUIRE_COMPOSE=1` to make missing Compose a hard failure.

Dependency audits are intentionally audit-only and are not part of the blocking validation path while current advisories are being triaged:

```bash
npm run audit:npm
npm run audit:npm:production
npm run audit:composer
```

The older `security-check` and `security-check-force` npm scripts run `npm audit fix`; use the `audit:*` scripts above when you only want to report dependency advisories without mutating manifests or lockfiles.

`npm run audit:composer` requires Composer 2.4 or newer.

Production deployments are image-based and manual: every push validates the repo, and running the `CI and Deploy` workflow by hand on `main` (Actions → Run workflow) publishes a Docker Hub image that the VPS then pulls via `docker-compose.prod.yml`. Work is committed straight to `main`, which never deploys on its own.

Repository-wide Docker Hub credentials can be set with `gh secret set`, while production VPS values are best stored as `production` environment secrets. The `build-and-publish` job is not environment-scoped, so `DOCKERHUB_USERNAME`, `DOCKERHUB_TOKEN`, and the optional `GOOGLE_CHAT_WEBHOOK_URL` notification secret must exist as repository or organization secrets rather than only as environment secrets. `DOCKERHUB_USERNAME` is only used for Docker Hub login; published images are pushed to the `datasparklab` organization namespace. See `DEPLOYMENT.md` and `./bin/setup-github-secrets.sh` for the exact bootstrap flow.

Application versioning comes from the root `VERSION` file. The Laravel app exposes both the raw version (for example `0.0.9`) and the release tag (`v0.0.9`) to views. The deployment workflow publishes Docker images with an immutable `sha-<commit>` tag plus mutable `latest`, and production deploys use the immutable tag so previous images remain available for rollback.

## Modernization Snapshot

Current baseline:

- Laravel `12.x`
- PHP Composer constraint `^8.2`
- PHPUnit `11.5+`
- Node `20.20.x` LTS for Vite asset builds
- React `18`
- Vite `5`
- GitHub Actions now updated to Node 24-compatible action majors

Recommended upgrade path:

See [`UPGRADE_PATH.md`](UPGRADE_PATH.md) for the phased production-safe upgrade plan covering the completed Laravel `9 -> 10 -> 11 -> 12` backend phases plus remaining frontend modernization, database migrations, deployment safety, validation, rollback, and post-upgrade feature ideas.

High-level sequence:

1. Keep docs and validation scripts accurate
2. Add missing security, health-check, and deployment safeguards
3. Keep Laravel 12 backend validation green
4. Keep the Vite frontend build stable on Node LTS
5. Modernize React Router, Redux, styled-components, Ant Design, then React
6. Apply database improvements with zero-downtime expand/contract migrations
7. Split CI into reusable `validate`, `build-image`, and `deploy` workflows for cross-project reuse

### Working with the Application

```bash
# View application logs
docker-compose -f docker-compose.dev.yml logs -f edfolio-dev

# Access application container shell
docker exec -it edfolio-dev bash

# Run Laravel Artisan commands
docker exec edfolio-dev php artisan list
docker exec edfolio-dev php artisan migrate
docker exec edfolio-dev php artisan db:seed

# Clear caches
docker exec edfolio-dev php artisan cache:clear
docker exec edfolio-dev php artisan config:clear
docker exec edfolio-dev php artisan view:clear
```

### Frontend Development

```bash
# Build assets for development
docker exec edfolio-dev npm run dev

# Watch for changes (hot reload)
docker exec edfolio-dev npm run watch

# Build for production
docker exec edfolio-dev npm run production
```

### Database Management

```bash
# Access MySQL CLI
docker exec -it edfolio-db-dev mysql -u edfolio_user -p

# Create database backup
docker exec edfolio-dev ./bin/backup-manager.sh backup

# View database via phpMyAdmin
# Open http://localhost:8081 in browser
```

## Docker Configuration

### Available Compose Files

- `docker-compose.dev.yml` - Development stack with all services
- `docker-compose.prod.yml` - Production configuration

### Development Services

The development environment includes:
- **App Container** (`edfolio-dev`) - Laravel application
- **MySQL** (`edfolio-db-dev`) - Database server
- **Redis** (`edfolio-redis-dev`) - Cache and sessions
- **phpMyAdmin** (`edfolio-phpmyadmin-dev`) - Database GUI
- **MailHog** (`edfolio-mailhog-dev`) - Email testing

## Troubleshooting

### Common Issues

**Port conflicts:**
```bash
# Check what's using the ports
lsof -i :8080   # Application
lsof -i :3306   # MySQL
lsof -i :6379   # Redis

# Stop services if needed
docker-compose -f docker-compose.dev.yml down
```

**Permission errors:**
```bash
# Fix storage and cache permissions
docker exec edfolio-dev chown -R www-data:www-data storage bootstrap/cache
```

**Database connection issues:**
```bash
# Verify database is running
docker-compose -f docker-compose.dev.yml ps

# Check database logs
docker logs edfolio-db-dev

# Restart database
docker-compose -f docker-compose.dev.yml restart edfolio-db-dev
```

**Asset compilation failures:**
```bash
# Clear npm cache
docker exec edfolio-dev npm cache clean --force

# Reinstall dependencies
docker exec edfolio-dev npm install

# Rebuild assets
docker exec edfolio-dev npm run dev
```

**Fresh start (nuclear option):**
```bash
# Stop and remove all containers and volumes
docker-compose -f docker-compose.dev.yml down -v

# Rebuild everything from scratch
docker-compose -f docker-compose.dev.yml up -d --build
```

## Project Structure

```
edfolio/
├── app/                          # Laravel application code
│   ├── Console/                  # Artisan commands
│   ├── Http/
│   │   ├── Controllers/          # API and web controllers
│   │   └── Middleware/           # HTTP middleware
│   ├── Models/                   # Eloquent models
│   └── Services/                 # Business logic services
├── bin/                          # Utility scripts
│   ├── backup-manager.sh         # Database backup tool
│   ├── build.sh                  # Asset build script
│   ├── create-env.sh             # Environment setup
│   └── start-apache.sh           # Container startup script
├── config/                       # Laravel configuration
├── database/
│   ├── migrations/               # Database migrations
│   └── seeders/                  # Database seeders
├── public/                       # Public web root
│   └── build/                    # Compiled Vite assets
├── resources/
│   └── js/
│       └── client/               # React source code
├── routes/                       # API and web routes
├── storage/                      # Logs, cache, uploads
├── docker-compose.dev.yml        # Development Docker setup
├── docker-compose.prod.yml       # Production Docker setup
├── Dockerfile                    # Application Docker image
└── vite.config.js                # Vite configuration
```

## Documentation

Additional documentation is available in the following files:

- **[DEVELOPMENT.md](DEVELOPMENT.md)** - Detailed development environment guide
- **[DOCKER.md](DOCKER.md)** - Docker configuration and troubleshooting
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Production deployment guide
- **[CONTRIBUTING.md](CONTRIBUTING.md)** - Contribution guidelines

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Support

For issues, questions, or contributions:
- Open an issue in the [GitHub repository](https://github.com/edilsonsantos/edfolio/issues)
- Check existing documentation in the repository
- Review the [original ezfolio project](https://github.com/arifszn/ezfolio) for additional resources

---

**Built with Laravel + React** | **Dockerized for easy deployment** | **MIT Licensed**
