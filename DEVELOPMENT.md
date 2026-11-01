# ES Portfolio Development Environment

Complete Docker-based development setup with all required services.

## Quick Start

1. **Setup environment** (env files are gitignored; start from the template):
   ```bash
   cp .env.dev.example .env.dev   # then fill in the secrets
   ./bin/create-env.sh dev
   ```

2. **Start development environment**:
   ```bash
   docker-compose -f docker-compose.dev.yml up -d
   ```

3. **Access the application**:
   - **App**: http://localhost:8080
   - **phpMyAdmin**: http://localhost:8081
   - **MailHog**: http://localhost:8025
   - **Redis**: localhost:6379

## Services Included

### 🚀 **ES Portfolio App** (`edfolio-dev`)
- **Port**: 8080
- **Container**: Full Laravel application with automatic asset building
- **Features**: Hot reload, persistent storage, automatic migrations

### 🗄️ **MySQL Database** (`edfolio-db-dev`)
- **Port**: 3306
- **Database**: `edfolio`
- **User**: `edfolio_user`
- **Password**: `edfolio_dev_pass`
- **Root Password**: `edfolio_root_pass`

### 🔴 **Redis Cache** (`edfolio-redis-dev`)
- **Port**: 6379
- **Password**: `edfolio_redis_pass`
- **Persistent data**: Volume mounted

### 📧 **MailHog** (`edfolio-mailhog-dev`)
- **SMTP Port**: 1025
- **Web UI**: http://localhost:8025
- **Purpose**: Catch all outgoing emails for testing

### 🛠️ **phpMyAdmin** (`edfolio-phpmyadmin-dev`)
- **Port**: 8081
- **Purpose**: Database management interface
- **Auto-login**: Configured with database credentials

## Development Features

### 📁 **Persistent Volumes**
- `edfolio_dev_storage` - Laravel storage (logs, cache, uploads)
- `edfolio_dev_uploads` - User uploaded assets
- `edfolio_dev_node_modules` - Node.js dependencies (faster builds)
- `edfolio_dev_db` - MySQL database data
- `edfolio_dev_redis` - Redis data

### 🔄 **Automatic Features**
- **Database migrations** run on startup
- **Database seeding** for fresh installations
- **Asset building** with npm/Vite
- **Database backups** before migrations

## Common Commands

### Quality gates

Use the Node 20 LTS baseline before running local npm commands or validation.
The pinned `.nvmrc` matches CI/Docker and keeps Vite builds reproducible:

```bash
nvm install
nvm use
```

Enable the repository git hooks once per clone:

```bash
./bin/setup-git-hooks.sh
```

Run the standard local validation suite:

```bash
./bin/validate.sh
```

The validation script currently checks:
- `docker compose -f docker-compose.dev.yml config` or `docker-compose -f docker-compose.dev.yml config`
- `docker compose -f docker-compose.prod.yml config` or `docker-compose -f docker-compose.prod.yml config`
- `./vendor/bin/phpunit`
- `npm run lint`
- `npm run build`

If Docker Compose is not on `PATH`, the script warns and skips the Compose config check. Set `VALIDATE_REQUIRE_COMPOSE=1` to require Compose explicitly.

Audit dependencies without mutating manifests or lockfiles:

```bash
npm run audit:npm
npm run audit:npm:production
npm run audit:composer
```

These checks are documented rather than enforced in CI while existing npm advisories and Composer audit availability are being triaged. The legacy `security-check` scripts still run `npm audit fix`.

`npm run audit:composer` requires Composer 2.4 or newer.

### 🐳 **Docker Operations**
```bash
# Start all services
docker-compose -f docker-compose.dev.yml up -d

# Stop all services
docker-compose -f docker-compose.dev.yml down

# View logs
docker-compose -f docker-compose.dev.yml logs -f

# Rebuild containers
docker-compose -f docker-compose.dev.yml up -d --build

# Clean volumes (fresh start)
docker-compose -f docker-compose.dev.yml down -v
```

### 🔧 **Application Commands**
```bash
# Access app container
docker exec -it edfolio-dev bash

# Run Laravel commands
docker exec edfolio-dev php artisan migrate
docker exec edfolio-dev php artisan db:seed
docker exec edfolio-dev php artisan cache:clear

# Build assets
docker exec edfolio-dev npm run dev
docker exec edfolio-dev npm run watch

# View application logs
docker exec edfolio-dev tail -f storage/logs/laravel.log
```

### 🗄️ **Database Operations**
```bash
# Access MySQL directly
docker exec -it edfolio-db-dev mysql -u edfolio_user -p

# Create database backup
docker exec edfolio-dev php artisan backup:run

# Restore database
docker exec -i edfolio-db-dev mysql -u edfolio_user -p edfolio < backup.sql
```

## Configuration

### 🔧 **Environment Variables**
Key development settings in `.env.dev`:

```env
# Database (automatically set by docker-compose)
DB_HOST=db
DB_DATABASE=edfolio
DB_USERNAME=edfolio_user
DB_PASSWORD=edfolio_dev_pass

# Redis (automatically set)
REDIS_HOST=redis
REDIS_PASSWORD=edfolio_redis_pass

# Mail (MailHog)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

### 🎯 **Development Behavior**
```env
# Docker startup behavior
SKIP_ASSET_BUILD=false      # Build frontend assets
FORCE_FRESH_DB=false        # Don't wipe DB on startup
CREATE_DB_BACKUP=true       # Backup before migrations
RUN_MIGRATIONS=auto         # Run migrations if needed
RUN_SEEDING=auto           # Seed if tables are empty
```

## Troubleshooting

### 🐛 **Common Issues**

**Port Conflicts**:
```bash
# Check what's using ports
lsof -i :8080
lsof -i :3306
lsof -i :6379

# Stop conflicting services
sudo systemctl stop mysql
sudo systemctl stop redis
```

**Permission Issues**:
```bash
# Fix file permissions
sudo chown -R $USER:$USER .
docker exec edfolio-dev chown -R www-data:www-data storage bootstrap/cache
```

**Database Connection**:
```bash
# Check database is running
docker exec edfolio-db-dev mysqladmin ping

# Reset database
docker-compose -f docker-compose.dev.yml down
docker volume rm edfolio_dev_db
docker-compose -f docker-compose.dev.yml up -d
```

**Asset Building Failures**:
```bash
# Clear npm cache
docker exec edfolio-dev npm cache clean --force

# Rebuild node_modules
docker volume rm edfolio_dev_node_modules
docker-compose -f docker-compose.dev.yml up -d --build
```

### 📊 **Health Checks**

```bash
# Check all containers
docker-compose -f docker-compose.dev.yml ps

# Test application
curl http://localhost:8080

# Test database
docker exec edfolio-dev php artisan tinker --execute="DB::connection()->getPdo();"

# Test Redis
docker exec edfolio-redis-dev redis-cli -a edfolio_redis_pass ping
```

## VS Code Integration

### 🔌 **Recommended Extensions**
- **Docker** - Container management
- **Laravel Extension Pack** - Laravel development tools
- **PHP Intelephense** - PHP language server

### ⚙️ **Settings**
Add to `.vscode/settings.json`:

```json
{
    "php.suggest.basic": false,
    "php.executablePath": "/usr/bin/php",
    "intelephense.environment.phpVersion": "8.2"
}
```

## Production Differences

The development environment differs from production:

| Feature | Development | Production |
|---------|-------------|------------|
| Database | Local MySQL | External MySQL |
| Redis | Local Redis | External Redis |
| Mail | MailHog | SMTP/SES |
| Assets | Auto-build | Pre-built |
| Debug | Enabled | Disabled |
| Volumes | Multiple | Single bind mount |

## Modernization Status

Current project baseline:

- Laravel `12.x`
- PHP Composer constraint `^8.2`
- PHPUnit `11.5+`
- React `18`
- Vite `5`

Recommended next engineering phases:

1. Add PHP static analysis and formatting tooling
2. Keep Laravel 12 backend validation green
3. Keep the Vite frontend build stable on Node LTS
4. Extract reusable CI workflows for validation, image build, and deploy

## Next Steps

1. **Code changes** are automatically reflected (volume mounted)
2. **Database changes** persist between restarts
3. **Check email** at http://localhost:8025
4. **Manage database** at http://localhost:8081
5. **View logs** with docker commands above

Happy coding! 🚀
