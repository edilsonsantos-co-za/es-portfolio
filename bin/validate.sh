#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

require_file() {
  local path="$1"
  local hint="$2"
  if [[ ! -e "$path" ]]; then
    printf 'Missing required dependency or file: %s\n%s\n' "$path" "$hint" >&2
    exit 1
  fi
}

printf '[validate] Checking Docker Compose config...\n'
docker compose -f docker-compose.dev.yml config >/dev/null
docker compose -f docker-compose.prod.yml config >/dev/null

require_file "vendor/bin/phpunit" "Run: composer install --no-interaction"
require_file "node_modules" "Run: npm ci --legacy-peer-deps --no-audit --no-fund"

printf '[validate] Running PHPUnit...\n'
./vendor/bin/phpunit

printf '[validate] Building frontend assets...\n'
npm run build

printf '[validate] All checks passed.\n'
