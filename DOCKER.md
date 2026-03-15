# Docker Configuration Guide

This application supports both development and production Docker environments with automatic asset compilation.

## Environment Configurations

### Development Environment

Use `docker-compose.dev.yml` for local development:

```bash
# Build and start development environment
docker-compose -f docker-compose.dev.yml up --build

# Or run in background
docker-compose -f docker-compose.dev.yml up -d --build
```

**Features:**
- Uses `.env.dev` for environment configuration
- Volume mounts for live development
- Auto migrations and seeding on startup (`RUN_MIGRATIONS=auto`, `RUN_SEEDING=auto`)
- Fresh DB reset support via `FORCE_FRESH_DB=true`

### Production Environment

Use `docker-compose.prod.yml` for production:

```bash
# Build and start production environment
docker-compose -f docker-compose.prod.yml up --build

# Or run in background
docker-compose -f docker-compose.prod.yml up -d --build
```

**Features:**
- Uses `APP_ENV=production`
- Compiles assets with `npm run production` (optimized, minified)
- Runs from the built image with no full-repo bind mount
- Keeps only explicit named volumes for backups and local media storage
- Debug mode disabled

## Asset Compilation

Assets are automatically compiled in both scenarios:

1. **During Docker image build** - Assets are compiled based on `APP_ENV` build argument
2. **During container startup** - Assets are rebuilt to ensure they're current

### Manual Asset Building

You can also build assets manually:

```bash
# For development
npm run build:dev

# For production
npm run build:prod

# Environment-aware build (uses APP_ENV variable)
npm run build
```

## Environment Variables

Key environment variables that affect asset compilation:

- `APP_ENV`: Determines build type (`local`/`development` = dev build, `production` = prod build)
- `APP_DEBUG`: Debug mode (true/false)
- `APP_URL`: Application URL
- `MEDIA_DISK`: Upload target disk (`public` or `s3`)

### Production Media Storage

Production no longer bind-mounts the repository into `/var/www/html`. This prevents `repo:upload` rsync operations from rewriting files inside the running container before a rebuild.

- `MEDIA_DISK=public`: uploads stay on the `edfolio_storage` named volume.
- `MEDIA_DISK=s3`: uploads are written to S3-compatible object storage instead of the container volume.

For S3 mode, configure:

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

## Troubleshooting

### Assets Not Loading

If admin JS files return 404 errors:

1. Check if assets were compiled:
   ```bash
   docker exec edfolio-prod ls -la /var/www/html/public/js/client/admin/roots/
   ```

2. Check the one-shot asset builder logs:
   ```bash
   docker logs edfolio-assets --tail=200
   ```

3. Inspect the shared asset volume inside the app container:
   ```bash
   docker exec edfolio-prod ls -la /var/www/html/.frontend-assets/js/client/admin/roots/
   docker exec edfolio-prod ls -la /var/www/html/.frontend-assets/mix-manifest.json
   ```

4. If the asset builder fails, rebuild locally to compare behavior:
   ```bash
   npm run build
   ```

5. Rebuild production containers:
   ```bash
   docker compose -f docker-compose.prod.yml up -d --build
   ```

6. Check app container logs:
   ```bash
   docker logs edfolio-prod --tail=200
   ```

### Switching Environments

To switch from development to production:

1. Stop current containers:
   ```bash
   docker-compose -f docker-compose.dev.yml down
   ```

2. Build and start with production config:
   ```bash
   docker-compose -f docker-compose.prod.yml up --build
   ```

## File Structure

```
├── docker-compose.dev.yml      # Development configuration
├── docker-compose.prod.yml     # Production configuration
├── Dockerfile                  # Multi-environment Dockerfile
├── bin/start-apache.sh            # Startup script with asset building
├── scripts/build.js           # Cross-platform build script
└── .dockerignore              # Docker ignore file
```

## Default Admin Credentials

- **Email:** admin@admin.com
- **Password:** 12345

⚠️ **Important:** Change these credentials immediately after first login in production!
