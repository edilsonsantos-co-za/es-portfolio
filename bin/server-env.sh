#!/usr/bin/env bash
# Add what's missing to .env.prod on the VPS before a deploy. Run by .github/workflows/ci-deploy.yml as
# the deploy user, from the app folder. Never overwrites a value that is already set; prints no secret.
#
#   - LOGTO_* once the SSO deploy has registered edfolio-webapp (its secret is in the SSO env file)
#   - Mailtrap sandbox mail when MAILTRAP_USERNAME / MAILTRAP_PASSWORD are passed in
set -euo pipefail

ENV_FILE=.env.prod
SSO_ENV=/workspace/datasparklab/core-sso-webapp/.env.production
SSO_APP_ID=edfolio-webapp

get_env() { grep -E "^$2=" "$1" 2>/dev/null | tail -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' || true; }
set_env() {
  local tmp; tmp="$(mktemp)"
  awk -v k="$1" -v v="$2" 'BEGIN{d=0} index($0, k"=")==1 {print k"="v; d=1; next} {print} END{if(!d) print k"="v}' "$ENV_FILE" > "$tmp"
  cat "$tmp" > "$ENV_FILE"; rm -f "$tmp"
}

[ -f "$ENV_FILE" ] || { echo "::error::$PWD/$ENV_FILE is missing on the server."; exit 1; }
echo "Checking $ENV_FILE"

var="SSO_APP_SECRET_$(printf '%s' "$SSO_APP_ID" | tr '[:lower:]-' '[:upper:]_')"
secret="$( [ -r "$SSO_ENV" ] && get_env "$SSO_ENV" "$var" || true)"
if [ -n "$secret" ] && [ -z "$(get_env "$ENV_FILE" LOGTO_APP_SECRET)" ]; then
  set_env LOGTO_ENDPOINT https://sso.datasparklab.com
  set_env LOGTO_INTERNAL_ENDPOINT http://dsl-sso:3001
  set_env LOGTO_APP_ID "$SSO_APP_ID"
  set_env LOGTO_APP_SECRET "$secret"
  echo "  linked to SSO as $SSO_APP_ID"
fi

if [ -n "${MAILTRAP_USERNAME:-}" ] && [ -n "${MAILTRAP_PASSWORD:-}" ] && [ "$(get_env "$ENV_FILE" MAIL_HOST)" != "sandbox.smtp.mailtrap.io" ]; then
  set_env MAIL_MAILER smtp
  set_env MAIL_HOST sandbox.smtp.mailtrap.io
  set_env MAIL_PORT 2525
  set_env MAIL_USERNAME "$MAILTRAP_USERNAME"
  set_env MAIL_PASSWORD "$MAILTRAP_PASSWORD"
  set_env MAIL_ENCRYPTION null
  echo "  mail goes to the Mailtrap sandbox"
fi

echo "$ENV_FILE ready"
