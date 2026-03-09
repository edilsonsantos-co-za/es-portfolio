#!/bin/bash

# Simplified environment setup script - dev and production only
# Usage: ./create-env.sh [dev|prod]
# This will create .env from the appropriate template and set it as active

ENV=${1:-prod}

# Validate environment
if [ "$ENV" != "dev" ] && [ "$ENV" != "prod" ]; then
    echo "Error: Environment must be 'dev' or 'prod'"
    echo "Usage: ./create-env.sh [dev|prod]"
    exit 1
fi

# Set source files
if [ "$ENV" = "dev" ]; then
    SOURCE_FILE=".env.dev"
    echo "Setting up DEVELOPMENT environment..."
elif [ "$ENV" = "prod" ]; then
    SOURCE_FILE=".env.prod"
    echo "Setting up PRODUCTION environment..."
fi

# Check if source file exists
if [ ! -f "$SOURCE_FILE" ]; then
    echo "Error: $SOURCE_FILE not found!"
    echo "Please ensure you have both .env.dev and .env.prod files"
    exit 1
fi

# Backup current .env if it exists
if [ -f ".env" ]; then
    echo "Backing up current .env to .env.backup..."
    cp .env .env.backup
fi

# Copy the appropriate environment file to .env
echo "Copying $SOURCE_FILE to .env..."
cp "$SOURCE_FILE" ".env"

echo "✓ Environment setup complete!"
echo "Active environment: $ENV"
echo ""
echo "Next steps:"
echo "1. Run: php artisan config:clear"
echo "2. Run: php artisan cache:clear" 
echo "3. Generate APP_KEY if needed: php artisan key:generate"