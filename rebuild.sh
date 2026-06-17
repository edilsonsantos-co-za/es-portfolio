#!/bin/bash

# Helper script to rebuild assets
# Run with: sudo ./rebuild.sh

# Remove old build directory
rm -rf public/build

# Run the build
npm run build

# Fix permissions if needed
chown -R eddie:eddie public/build 2>/dev/null || true
