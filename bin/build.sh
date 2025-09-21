#!/bin/bash

# Build script for edfolio assets

echo "Installing npm dependencies..."
npm ci

echo "Building assets for production..."
npm run production

echo "Asset build complete!"