#!/usr/bin/env bash
set -euo pipefail

SCRIPT_PATH="${BASH_SOURCE[0]}"
SCRIPT_DIR="${SCRIPT_PATH%/*}"
ROOT_DIR="$(cd "${SCRIPT_DIR:-.}/.." && pwd)"
cd "$ROOT_DIR"

append_path_if_dir() {
  local dir="$1"
  if [[ -d "$dir" && ":$PATH:" != *":$dir:"* ]]; then
    PATH="$dir:$PATH"
  fi
}

bootstrap_path() {
  local node_bin

  append_path_if_dir /opt/homebrew/bin
  append_path_if_dir /opt/homebrew/sbin
  append_path_if_dir /usr/local/bin
  append_path_if_dir /usr/local/sbin
  append_path_if_dir /Applications/Docker.app/Contents/Resources/bin

  for node_bin in "$HOME"/.nvm/versions/node/*/bin; do
    append_path_if_dir "$node_bin"
  done

  export PATH
}

COMPOSE_AVAILABLE=0
COMPOSE_WARNING_SHOWN=0
COMPOSE_CMD=()

init_compose_command() {
  if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    COMPOSE_CMD=(docker compose)
    COMPOSE_AVAILABLE=1
    return
  fi

  if command -v docker-compose >/dev/null 2>&1; then
    COMPOSE_CMD=(docker-compose)
    COMPOSE_AVAILABLE=1
    return
  fi
}

compose_config_check() {
  if [[ "$COMPOSE_AVAILABLE" == "1" ]]; then
    "${COMPOSE_CMD[@]}" -f "$1" config >/dev/null
    return
  fi

  local message='Skipping Docker Compose validation because neither `docker compose` nor `docker-compose` is available on PATH.'

  if [[ "${VALIDATE_REQUIRE_COMPOSE:-0}" == "1" ]]; then
    printf '%s\nSet up Docker tooling or unset VALIDATE_REQUIRE_COMPOSE.\n' "$message" >&2
    exit 1
  fi

  if [[ "$COMPOSE_WARNING_SHOWN" == "0" ]]; then
    printf '[validate] %s\n' "$message"
    COMPOSE_WARNING_SHOWN=1
  fi
}

require_file() {
  local path="$1"
  local hint="$2"
  if [[ ! -e "$path" ]]; then
    printf 'Missing required dependency or file: %s\n%s\n' "$path" "$hint" >&2
    exit 1
  fi
}

bootstrap_path
init_compose_command

PHPUNIT_APP_KEY_FALLBACK='base64:4d6rBKspCmgDurgcdnb5KJ0QUgTefTgaEsqURDZpH0M='

printf '[validate] Checking Docker Compose config...\n'
compose_config_check docker-compose.dev.yml
compose_config_check docker-compose.prod.yml

require_file "vendor/bin/phpunit" "Run: composer install --no-interaction"
require_file "node_modules" "Run: npm ci --legacy-peer-deps --no-audit --no-fund"

printf '[validate] Running PHPUnit...\n'
APP_ENV=testing APP_KEY="${APP_KEY:-$PHPUNIT_APP_KEY_FALLBACK}" ./vendor/bin/phpunit

printf '[validate] Building frontend assets...\n'
npm run build

printf '[validate] All checks passed.\n'
