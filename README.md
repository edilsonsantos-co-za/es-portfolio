# EdFolio

A modern, full-stack portfolio application built with Laravel and React, designed for developers and creatives to showcase their work professionally.

> **Note:** This project is a fork of [ezfolio](https://github.com/arifszn/ezfolio) by [arifszn](https://github.com/arifszn).

## Tech Stack

### Backend
- **Laravel 9** - PHP framework for robust backend API
- **MySQL** - Primary database
- **Redis** - Caching and session management
- **JWT Authentication** - Secure admin authentication

### Frontend
- **React 18** - Modern UI library
- **Ant Design** - Professional UI component library
- **Redux** - State management
- **React Router** - Client-side routing
- **Styled Components** - CSS-in-JS styling

### Infrastructure
- **Docker** - Containerized development and production environments
- **Apache** - Web server
- **Laravel Mix** - Asset compilation and bundling

## Features

- **Portfolio Management** - Showcase projects with descriptions, images, and links
- **Experience Timeline** - Display professional work history
- **Education Section** - Highlight academic achievements
- **Skills Management** - Organize and display technical skills
- **Services Offered** - List professional services
- **Contact Form** - Integrated messaging system
- **Admin Dashboard** - Full-featured admin panel for content management
- **Visitor Analytics** - Track portfolio visits and engagement
- **Theme Customization** - Dynamic theming support
- **Responsive Design** - Mobile-first, fully responsive layout

## Local Development

### Prerequisites
- Docker Desktop or Docker Engine
- Docker Compose
- Git

### Quick Start

1. **Clone the repository**
   ```bash
   git clone <repository-url>
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

# Database behavior
RUN_MIGRATIONS=auto|true|false      # Control migration execution
RUN_SEEDING=auto|true|false         # Control database seeding
FORCE_FRESH_DB=false                # Wipe database on startup
CREATE_DB_BACKUP=true               # Backup before migrations

# Build behavior
SKIP_ASSET_BUILD=false              # Skip frontend asset building
```

## Common Development Tasks

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

- `docker-compose.yml` - Base configuration
- `docker-compose.dev.yml` - Full development stack with all services
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
│   └── js/client/                # Compiled React application
├── resources/
│   └── js/
│       └── client/               # React source code
├── routes/                       # API and web routes
├── storage/                      # Logs, cache, uploads
├── docker-compose.dev.yml        # Development Docker setup
├── docker-compose.prod.yml       # Production Docker setup
├── Dockerfile                    # Application Docker image
└── webpack.mix.js                # Laravel Mix configuration
```

## Documentation

Additional documentation is available in the following files:

- **[DEVELOPMENT.md](DEVELOPMENT.md)** - Detailed development environment guide
- **[DOCKER.md](DOCKER.md)** - Docker configuration and troubleshooting
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Production deployment guide
- **[CONTRIBUTING.md](CONTRIBUTING.md)** - Contribution guidelines

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

Copyright (c) 2024 Edilson Santos

## Support

For issues, questions, or contributions, please refer to the documentation files above or open an issue in the repository.

---

**Built with Laravel + React** | **Dockerized for easy deployment** | **MIT Licensed**