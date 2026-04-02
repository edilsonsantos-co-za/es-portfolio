#!/bin/bash
set -e

# Set git safe directory to avoid ownership issues
git config --global --add safe.directory /var/www/html
git config --global --add safe.directory '*'

# Environment variables for startup behavior
SKIP_ASSET_BUILD=${SKIP_ASSET_BUILD:-false}
FORCE_FRESH_DB=${FORCE_FRESH_DB:-false}
CREATE_DB_BACKUP=${CREATE_DB_BACKUP:-false}
RUN_MIGRATIONS=${RUN_MIGRATIONS:-auto}
RUN_SEEDING=${RUN_SEEDING:-auto}

# Debug environment variables
echo "[DEBUG] Environment variables:"
echo "[DEBUG] SKIP_ASSET_BUILD='$SKIP_ASSET_BUILD'"
echo "[DEBUG] FORCE_FRESH_DB='$FORCE_FRESH_DB'" 
echo "[DEBUG] CREATE_DB_BACKUP='$CREATE_DB_BACKUP'"
echo "[DEBUG] RUN_MIGRATIONS='$RUN_MIGRATIONS'"
echo "[DEBUG] RUN_SEEDING='$RUN_SEEDING'"
echo "[DEBUG] APP_ENV='${APP_ENV:-production}'"

# Set up environment using new system
APP_ENV_ACTUAL=${APP_ENV:-production}
if [ "$APP_ENV_ACTUAL" = "development" ]; then
  SOURCE_ENV="dev"
else
  SOURCE_ENV="prod"
fi

echo "[STARTUP] Setting up $APP_ENV_ACTUAL environment using create-env.sh..."
if [ -f "./bin/create-env.sh" ]; then
  chmod +x ./bin/create-env.sh
  echo "[DEBUG] Running: ./bin/create-env.sh $SOURCE_ENV"
  ./bin/create-env.sh "$SOURCE_ENV" || {
    echo "[ERROR] create-env.sh failed with exit code $?"
    echo "[DEBUG] Checking if source env file exists: .env.$SOURCE_ENV"
    if [ -f ".env.$SOURCE_ENV" ]; then
      echo "[FALLBACK] Source file exists, copying directly..."
      cp ".env.$SOURCE_ENV" ".env" || {
        echo "[ERROR] Direct copy failed!"
        exit 1
      }
    else
      echo "[ERROR] Source env file .env.$SOURCE_ENV not found!"
      echo "[DEBUG] Available files:"
      ls -la .env* || echo "No .env files found"
      exit 1
    fi
  }
else
  echo "[WARNING] bin/create-env.sh not found, checking if .env exists..."
  if [ ! -f ".env" ]; then
    echo "[ERROR] No .env file found and bin/create-env.sh missing!"
    echo "[DEBUG] Available files:"
    ls -la .env* || echo "No .env files found"
    exit 1
  fi
fi

# Fix Dashboard.js issue at runtime (not build time)
if [ -f /var/www/html/resources/js/client/admin/components/dashboard/Dashboard.js ]; then
  echo "[STARTUP] Fixing Dashboard.js import issue..."
  sed -i 's/import Icon, { InfoCircleOutlined } from/import Icon from/g' /var/www/html/resources/js/client/admin/components/dashboard/Dashboard.js || echo "[WARNING] Dashboard.js fix failed"
fi

# Update Composer dependencies if needed (runtime flexibility)
echo "[STARTUP] Updating Composer dependencies if needed..."
cd /var/www/html

# Recreate required Laravel runtime directories in case they were excluded from the image
# build context or shadowed by mounted volumes.  The media upload subdirectories must be
# created explicitly because named Docker volumes mount over the image filesystem.
mkdir -p \
  /var/www/html/bootstrap/cache \
  /var/www/html/storage/app/public/projects \
  /var/www/html/storage/app/public/avatar \
  /var/www/html/storage/app/public/cover \
  /var/www/html/storage/app/public/cv \
  /var/www/html/storage/app/public/logo \
  /var/www/html/storage/app/public/favicon \
  /var/www/html/storage/app/public/meta-image \
  /var/www/html/storage/app/backups \
  /var/www/html/storage/framework/cache \
  /var/www/html/storage/framework/sessions \
  /var/www/html/storage/framework/views \
  /var/www/html/storage/logs

composer install --no-dev --optimize-autoloader --no-interaction || echo "[WARNING] Composer update failed, but continuing..."

# Set file permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
find /var/www/html/public -type d -exec chmod 755 {} \;
find /var/www/html/public -type f -exec chmod 644 {} \;

# Build frontend assets only when explicitly enabled and package.json exists.
if [ "$SKIP_ASSET_BUILD" != "true" ] && [ -f /var/www/html/package.json ]; then
  echo "[STARTUP] Building frontend assets for environment: ${APP_ENV:-production}..."
  cd /var/www/html
  
  # Clean npm cache to avoid memory issues
  echo "[STARTUP] Cleaning npm cache..."
  npm cache clean --force || echo "[WARNING] npm cache clean failed"
  
  # Install npm dependencies with memory-efficient options
  if [ ! -d /var/www/html/node_modules ]; then
    echo "[STARTUP] Installing npm dependencies with memory limits..."
    NODE_OPTIONS="--max-old-space-size=1024" npm ci --legacy-peer-deps --production=false --no-audit --no-fund || echo "[ERROR] npm install failed, but continuing..."
  fi
  
  # Build assets with memory limits and explicit failure handling
  echo "[STARTUP] Building assets with memory optimization..."
  BUILD_EXIT=0
  NODE_OPTIONS="--max-old-space-size=1024" APP_ENV=${APP_ENV:-production} npm run build 2>&1 | tee /tmp/npm-build.log || BUILD_EXIT=$?
  if [ "$BUILD_EXIT" -ne 0 ]; then
    echo "[ERROR] npm run build failed. Log output:"
    if [ -f /tmp/npm-build.log ]; then
      tail -20 /tmp/npm-build.log
    fi
    exit "$BUILD_EXIT"
  fi
  echo "[SUCCESS] Frontend asset building completed."
elif [ "$SKIP_ASSET_BUILD" = "true" ]; then
  FRONTEND_ASSET_DIR="/var/www/html/.frontend-assets"
  if [ -f "$FRONTEND_ASSET_DIR/.ready" ] && [ -f "$FRONTEND_ASSET_DIR/js/client/admin/roots/app.js" ] && [ -f "$FRONTEND_ASSET_DIR/js/client/frontend/roots/projects.js" ] && [ -f "$FRONTEND_ASSET_DIR/mix-manifest.json" ]; then
    echo "[STARTUP] Hydrating prebuilt frontend assets from shared volume..."
    rm -rf /var/www/html/public/js
    mkdir -p /var/www/html/public
    cp -R "$FRONTEND_ASSET_DIR/js" /var/www/html/public/js
    cp "$FRONTEND_ASSET_DIR/mix-manifest.json" /var/www/html/public/mix-manifest.json
    chown -R www-data:www-data /var/www/html/public/js /var/www/html/public/mix-manifest.json
    echo "[STARTUP] Skipping asset build (SKIP_ASSET_BUILD=true, precompiled assets detected)"
  elif [ -f /var/www/html/public/js/client/admin/roots/app.js ] && [ -f /var/www/html/public/js/client/frontend/roots/projects.js ] && [ -f /var/www/html/public/mix-manifest.json ]; then
    echo "[STARTUP] Skipping asset build (SKIP_ASSET_BUILD=true, image assets detected)"
  else
    echo "[ERROR] SKIP_ASSET_BUILD=true but precompiled frontend assets are missing."
    exit 1
  fi
else
  echo "[STARTUP] Skipping asset build - package.json missing"
fi

# Wait for external MySQL database to be available
echo "[STARTUP] Waiting for MySQL database at $DB_HOST to be ready..."
for i in {1..30}; do
  echo "[STARTUP] Testing database connection (attempt $i/30)..."
  if mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --skip-ssl --silent; then
    echo "[SUCCESS] Successfully connected to MySQL database at $DB_HOST"
    break
  fi
  echo "[STARTUP] Attempt $i/30: MySQL database at $DB_HOST not ready yet. Waiting 5 seconds..."
  sleep 5
done

if mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --skip-ssl --silent; then
  echo "[STARTUP] Database connected successfully. Running Laravel setup commands..."
  cd /var/www/html
  
  # Create database backup if requested
  if [ "$CREATE_DB_BACKUP" = "true" ]; then
    echo "[STARTUP] Creating database backup..."
    
    # Ensure backups directory exists in Laravel storage
    BACKUP_DIR="/var/www/html/storage/app/backups"
    mkdir -p "$BACKUP_DIR"
    chown -R www-data:www-data "$BACKUP_DIR"
    chmod -R 755 "$BACKUP_DIR"
    
    BACKUP_FILE="$BACKUP_DIR/edfolio_backup_$(date +%Y%m%d_%H%M%S).sql"
    echo "[STARTUP] Creating backup at: $BACKUP_FILE"
    
    mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE" || {
      echo "[WARNING] Database backup failed"
      BACKUP_FILE=""
    }
    
    if [ -n "$BACKUP_FILE" ] && [ -f "$BACKUP_FILE" ]; then
      BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
      echo "[SUCCESS] Database backup saved to: $BACKUP_FILE (Size: $BACKUP_SIZE)"
      
      # Keep only last 10 backups to prevent disk space issues
      echo "[STARTUP] Cleaning up old backups (keeping last 10)..."
      find "$BACKUP_DIR" -name "edfolio_backup_*.sql" -type f | sort | head -n -10 | xargs rm -f || echo "[WARNING] Backup cleanup failed"
    else
      echo "[ERROR] Backup file creation failed or file is empty"
    fi
  fi
  
  # Smart database management
  if [ "$FORCE_FRESH_DB" = "true" ]; then
    echo "[STARTUP] FORCE_FRESH_DB=true - Running fresh database migration and seeding..."
    php artisan migrate:fresh --seed --force || echo "[ERROR] Fresh migration/seeding failed, but continuing..."
  else
    # Check if migrations are needed
    SHOULD_MIGRATE="false"
    if [ "$RUN_MIGRATIONS" = "true" ]; then
      SHOULD_MIGRATE="true"
      echo "[STARTUP] RUN_MIGRATIONS=true - Migrations forced"
    elif [ "$RUN_MIGRATIONS" = "auto" ]; then
      # Check if there are pending migrations
      if php artisan migrate:status --pending 2>/dev/null | grep -q "Pending"; then
        SHOULD_MIGRATE="true"
        echo "[STARTUP] Pending migrations detected - will run migrations"
      else
        echo "[STARTUP] No pending migrations detected"
      fi
    else
      echo "[STARTUP] RUN_MIGRATIONS=$RUN_MIGRATIONS - Skipping migrations"
    fi

    # Run migrations if needed
    if [ "$SHOULD_MIGRATE" = "true" ]; then
      echo "[STARTUP] Running database migrations..."
      php artisan migrate --force || echo "[ERROR] Migration failed, but continuing..."
    fi

    # Check if seeding is needed  
    SHOULD_SEED="false"
    if [ "$RUN_SEEDING" = "true" ]; then
      SHOULD_SEED="true"
      echo "[STARTUP] RUN_SEEDING=true - Seeding forced"
    elif [ "$RUN_SEEDING" = "auto" ]; then
      # Check if critical tables are empty (indicating first run or need for seeding)
      ABOUT_COUNT=$(php artisan tinker --execute="echo App\Models\About::count();" 2>/dev/null | tail -n 1 | tr -d '\n' || echo "0")
      PORTFOLIO_COUNT=$(php artisan tinker --execute="echo DB::table('portfolio_configs')->count();" 2>/dev/null | tail -n 1 | tr -d '\n' || echo "0")
      
      if [ "$ABOUT_COUNT" = "0" ] || [ "$PORTFOLIO_COUNT" = "0" ]; then
        SHOULD_SEED="true"
        echo "[STARTUP] Critical tables empty (About: $ABOUT_COUNT, Portfolio: $PORTFOLIO_COUNT) - will run seeding"
      else
        echo "[STARTUP] Database appears to be seeded (About: $ABOUT_COUNT, Portfolio: $PORTFOLIO_COUNT)"
      fi
    else
      echo "[STARTUP] RUN_SEEDING=$RUN_SEEDING - Skipping seeding"
    fi

    # Run seeding if needed
    if [ "$SHOULD_SEED" = "true" ]; then
      echo "[STARTUP] Running database seeding..."
      php artisan db:seed --force --verbose 2>&1 | tee /tmp/seeder.log || echo "[ERROR] Seeding failed, but continuing..."
      echo "[STARTUP] Seeding completed. Checking seeder logs..."
      if [ -f /tmp/seeder.log ]; then
        echo "[DEBUG] Seeder output:"
        cat /tmp/seeder.log
      fi
    fi
  fi
  
  echo "[STARTUP] Verifying critical tables are populated..."
  php artisan tinker --execute="echo 'About records: ' . \App\Models\About::count(); echo 'Portfolio config records: ' . DB::table('portfolio_configs')->count();" || echo "[ERROR] Table verification failed"
  
  echo "[STARTUP] Clearing application cache..."
  php artisan cache:clear || echo "[ERROR] Cache clear failed, but continuing..."
  echo "[STARTUP] Clearing config cache..."
  php artisan config:clear || echo "[ERROR] Config clear failed, but continuing..."
  echo "[STARTUP] Clearing view cache..."
  php artisan view:clear || echo "[ERROR] View clear failed, but continuing..."
  echo "[STARTUP] Creating storage symlink..."
  if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link || echo "[WARN] storage:link failed"
  else
    echo "[INFO] Storage symlink already exists"
  fi
  
  # Only generate key if APP_KEY is empty or missing
  echo "[STARTUP] Checking application key..."
  if grep -q "^APP_KEY=$" .env || ! grep -q "^APP_KEY=" .env || [ -z "$(grep '^APP_KEY=' .env | cut -d'=' -f2)" ]; then
    echo "[STARTUP] Generating application key..."
    php artisan key:generate --force || echo "[ERROR] Key generation failed, but continuing..."
  else
    echo "[STARTUP] APP_KEY already set, skipping key generation"
  fi
  
  # Always generate JWT secret to ensure it's set
  echo "[STARTUP] Generating JWT secret key..."
  
  # First clear any existing corrupted JWT_SECRET entries
  sed -i '/^JWT_SECRET=/d' .env || echo "[WARNING] Could not clean JWT_SECRET from .env"
  
  # Generate a fresh JWT secret
  JWT_SECRET=$(openssl rand -base64 64 | tr -d '\n')
  echo "" >> .env  # Add newline to ensure proper formatting
  echo "JWT_SECRET=$JWT_SECRET" >> .env
  
  echo "[SUCCESS] JWT secret set in .env"
  
  # Ensure About record exists for portfolio to work properly
  echo "[STARTUP] Ensuring About record exists..."
  php artisan tinker --execute="
  try {
    \$about = resolve('App\Services\Contracts\AboutInterface');
    \$existingCount = \App\Models\About::count();
    if (\$existingCount == 0) {
      echo 'Creating default About record...' . PHP_EOL;
      \$data = [
        'name' => 'Portfolio Owner',
        'email' => 'portfolio@example.com',
        'avatar' => 'assets/common/img/avatar/default.png',
        'cover' => 'assets/common/img/cover/default.png',
        'phone' => '12025550191',
        'address' => '123 Portfolio Street',
        'description' => 'Welcome to my portfolio. I am a passionate developer.',
        'taglines' => ['I am a Developer', 'I am a Problem Solver'],
        'social_links' => [],
        'seederCV' => 'assets/common/cv/default.pdf'
      ];
      \$result = \$about->store(\$data);
      echo 'About record created successfully.' . PHP_EOL;
    } else {
      echo 'About record already exists, count: ' . \$existingCount . PHP_EOL;
    }
  } catch (Exception \$e) {
    echo 'Error creating About record: ' . \$e->getMessage() . PHP_EOL;
  }
  " || echo "[ERROR] About record creation failed, but continuing..."
  
  echo "[SUCCESS] Laravel setup completed successfully!"
else
  echo "[ERROR] Could not connect to MySQL database at $DB_HOST after 30 attempts. Will not run database migrations and seeding."
fi

# Start Apache
echo "[STARTUP] Starting Apache web server..."
apache2-foreground
