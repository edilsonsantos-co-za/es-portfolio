# edfolio

A modern portfolio application.

## Local Development

### Prerequisites
- Docker
- Docker Compose
- Git

### Setup
1. Clone the repository
   ```
   git clone <repository-url>
   cd edfolio
   ```

2. Set up development environment
   ```
   ./bin/create-env.sh dev
   ```

3. Start the Docker containers
   ```
   docker compose -f docker-compose.dev.yml up -d
   ```

4. Access the application at http://localhost:8080

## Production Deployment

The application is automatically deployed to the VPS using GitHub Actions when changes are pushed to the main branch.

### Deployment Configuration

- **Server**: VPS hosted at `$VPS_HOST`
- **Path**: `/apps/edilsonsantos/edfolio/`
- **URL**: https://edilsonsantos.co.za

### Manual Deployment

If you need to deploy manually, you can:

1. SSH into the server
   ```
   ssh <username>@<server-ip>
   ```

2. Navigate to the application directory
   ```
   cd /apps/edilsonsantos/edfolio/`
   ```

3. Pull the latest changes
   ```
   git pull origin main
   ```

4. Set up production environment
   ```
   ./bin/create-env.sh prod
   ```

5. Restart the containers
   ```
   docker compose -f docker-compose.prod.yml down
   docker compose -f docker-compose.prod.yml up -d --build
   ```

## Environment Configuration

The project uses a simplified environment setup with only two environments:

- `.env.dev` - Development environment template
- `.env.prod` - Production environment template  
- `.env` - Active environment (created by `create-env.sh`)

### Environment Setup

Use the environment setup script to switch between environments:

```bash
# Set up development environment
./bin/create-env.sh dev

# Set up production environment  
./bin/create-env.sh prod
```

This script will:
- Copy the appropriate template (`.env.dev` or `.env.prod`) to `.env`
- Backup any existing `.env` file
- Clear Laravel's config cache

## Docker Configuration

- `docker-compose.dev.yml` - Development configuration
- `docker-compose.prod.yml` - Production configuration