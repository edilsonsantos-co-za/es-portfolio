#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Usage:
  ./bin/setup-github-secrets.sh OWNER/REPO \
    --dockerhub-username USERNAME \
    --dockerhub-token TOKEN \
    --deploy-host HOST \
    --deploy-user USER \
    --deploy-path PATH \
    --deploy-key-file /path/to/private_key \
    [--environment production]

This script configures the secrets required by the Docker Hub deployment workflow.
The Docker Hub username is used for authentication only; images are published to the
fixed `datasparklab` organization namespace, so the account must have push access there.

Repository secrets:
  - DOCKERHUB_USERNAME
  - DOCKERHUB_TOKEN

Environment secrets (default environment: production):
  - DEPLOY_HOST
  - DEPLOY_USER
  - DEPLOY_PATH
  - DEPLOY_SSH_PRIVATE_KEY

Requirements:
  - GitHub CLI (`gh`) must be installed and authenticated
  - You must have permission to manage secrets for the target repository
EOF
}

if [[ $# -lt 1 ]]; then
  usage >&2
  exit 1
fi

REPO="$1"
shift

ENVIRONMENT="production"
DOCKERHUB_USERNAME=""
DOCKERHUB_TOKEN=""
DEPLOY_HOST=""
DEPLOY_USER=""
DEPLOY_PATH=""
DEPLOY_KEY_FILE=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dockerhub-username)
      DOCKERHUB_USERNAME="$2"
      shift 2
      ;;
    --dockerhub-token)
      DOCKERHUB_TOKEN="$2"
      shift 2
      ;;
    --deploy-host)
      DEPLOY_HOST="$2"
      shift 2
      ;;
    --deploy-user)
      DEPLOY_USER="$2"
      shift 2
      ;;
    --deploy-path)
      DEPLOY_PATH="$2"
      shift 2
      ;;
    --deploy-key-file)
      DEPLOY_KEY_FILE="$2"
      shift 2
      ;;
    --environment)
      ENVIRONMENT="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      printf 'Unknown argument: %s\n\n' "$1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

require_value() {
  local name="$1"
  local value="$2"
  if [[ -z "$value" ]]; then
    printf 'Missing required argument for %s\n\n' "$name" >&2
    usage >&2
    exit 1
  fi
}

require_value "--dockerhub-username" "$DOCKERHUB_USERNAME"
require_value "--dockerhub-token" "$DOCKERHUB_TOKEN"
require_value "--deploy-host" "$DEPLOY_HOST"
require_value "--deploy-user" "$DEPLOY_USER"
require_value "--deploy-path" "$DEPLOY_PATH"
require_value "--deploy-key-file" "$DEPLOY_KEY_FILE"

if [[ ! -f "$DEPLOY_KEY_FILE" ]]; then
  printf 'Deploy key file not found: %s\n' "$DEPLOY_KEY_FILE" >&2
  exit 1
fi

if ! command -v gh >/dev/null 2>&1; then
  printf 'GitHub CLI (gh) is required.\n' >&2
  exit 1
fi

printf 'Configuring repository secrets for %s...\n' "$REPO"
gh secret set DOCKERHUB_USERNAME --repo "$REPO" --body "$DOCKERHUB_USERNAME"
gh secret set DOCKERHUB_TOKEN --repo "$REPO" --body "$DOCKERHUB_TOKEN"

printf 'Configuring %s environment secrets for %s...\n' "$ENVIRONMENT" "$REPO"
gh secret set DEPLOY_HOST --repo "$REPO" --env "$ENVIRONMENT" --body "$DEPLOY_HOST"
gh secret set DEPLOY_USER --repo "$REPO" --env "$ENVIRONMENT" --body "$DEPLOY_USER"
gh secret set DEPLOY_PATH --repo "$REPO" --env "$ENVIRONMENT" --body "$DEPLOY_PATH"
gh secret set DEPLOY_SSH_PRIVATE_KEY --repo "$REPO" --env "$ENVIRONMENT" < "$DEPLOY_KEY_FILE"

printf 'Done. Repository secrets and %s environment secrets are configured for %s.\n' "$ENVIRONMENT" "$REPO"
