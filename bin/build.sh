#!/bin/bash

# Build script for ES Portfolio assets

echo "Installing npm dependencies..."
npm ci

echo "Building assets for production..."
npm run production

echo "Asset build complete!"