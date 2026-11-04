#!/usr/bin/env bash
# Add what's missing to .env.prod on the VPS before a deploy. Run by .github/workflows/ci-deploy.yml as
# the deploy user, from the app folder. Never overwrites a value that is already set (mail follows the SMTP_* secrets); prints no secret.
#
#   - LOGTO_* once the SSO deploy has registered es-portfolio-webapp (its secret is in the SSO env file)
#   - mail from the SMTP_* secrets; the Mailtrap sandbox only while no mail is configured
set -euo pipefail

ENV_FILE=.env.prod
SSO_ENV=/workspace/datasparklab/core-sso-webapp/.env.production
SSO_APP_ID=es-portfolio-webapp

get_env() { grep -E "^$2=" "$1" 2>/dev/null | tail -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' || true; }
set_env() {
  local tmp; tmp="$(mktemp)"
  # The value goes through the environment: awk -v would interpret backslashes in it
  SET_ENV_VALUE="$2" awk -v k="$1" 'BEGIN{d=0; v=ENVIRON["SET_ENV_VALUE"]} index($0, k"=")==1 {print k"="v; d=1; next} {print} END{if(!d) print k"="v}' "$ENV_FILE" > "$tmp"
  cat "$tmp" > "$ENV_FILE"; rm -f "$tmp"
}

[ -f "$ENV_FILE" ] || { echo "::error::$PWD/$ENV_FILE is missing on the server."; exit 1; }
echo "Checking $ENV_FILE"

# Behind the reverse proxy Laravel sees plain http; force https links (SSO redirect URIs, assets)
if get_env "$ENV_FILE" APP_URL | grep -q '^https://' && [ -z "$(get_env "$ENV_FILE" FORCE_HTTPS)" ]; then
  set_env FORCE_HTTPS true; echo "  set FORCE_HTTPS"
fi

var="SSO_APP_SECRET_$(printf '%s' "$SSO_APP_ID" | tr '[:lower:]-' '[:upper:]_')"
secret="$( [ -r "$SSO_ENV" ] && get_env "$SSO_ENV" "$var" || true)"
# Also switches an env file still linked to another client (the app was renamed in Logto)
if [ -n "$secret" ] && { [ -z "$(get_env "$ENV_FILE" LOGTO_APP_SECRET)" ] || [ "$(get_env "$ENV_FILE" LOGTO_APP_ID)" != "$SSO_APP_ID" ]; }; then
  set_env LOGTO_ENDPOINT https://sso.datasparklab.com
  set_env LOGTO_INTERNAL_ENDPOINT http://dsl-sso:3001
  set_env LOGTO_APP_ID "$SSO_APP_ID"
  set_env LOGTO_APP_SECRET "$secret"
  echo "  linked to SSO as $SSO_APP_ID"
fi

# Real mail (GitHub secrets SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, MAIL_FROM_ADDRESS), passed in as
# one base64 blob (SMTP_B64) so any character in a password survives the trip
if [ -n "${SMTP_B64:-}" ]; then
  while IFS= read -r line; do
    [ -n "$line" ] && export "${line%%=*}=${line#*=}"
  done < <(printf '%s' "$SMTP_B64" | base64 -d)
fi
current_host="$(get_env "$ENV_FILE" MAIL_HOST)"
if [ -n "${SMTP_HOST:-}" ]; then
  # Real delivery: rewritten when the secrets change, so rotating a password is just updating the secret
  changed=0
  # Login values double-quoted and escaped, so #, spaces, quotes or $ in a password survive .env parsing
  dq() { local v="${1//\\/\\\\}"; v="${v//\"/\\\"}"; v="${v//\$/\\\$}"; printf '"%s"' "$v"; }
  raw() { grep -E "^$1=" "$ENV_FILE" 2>/dev/null | tail -1 | cut -d= -f2- || true; }
  for kv in "MAIL_USERNAME=$(dq "${SMTP_USERNAME:-}")" "MAIL_PASSWORD=$(dq "${SMTP_PASSWORD:-}")"; do
    k="${kv%%=*}"; v="${kv#*=}"
    if [ "$(raw "$k")" != "$v" ]; then set_env "$k" "$v"; changed=1; fi
  done
  for kv in "MAIL_MAILER=smtp" "MAIL_HOST=$SMTP_HOST" "MAIL_PORT=${SMTP_PORT:-587}" "MAIL_ENCRYPTION=tls"; do
    k="${kv%%=*}"; v="${kv#*=}"
    if [ "$(get_env "$ENV_FILE" "$k")" != "$v" ]; then set_env "$k" "$v"; changed=1; fi
  done
  if [ -n "${MAIL_FROM_ADDRESS:-}" ] && [ "$(get_env "$ENV_FILE" MAIL_FROM_ADDRESS)" != "$MAIL_FROM_ADDRESS" ]; then set_env MAIL_FROM_ADDRESS "$MAIL_FROM_ADDRESS"; changed=1; fi
  [ "$changed" = 1 ] && echo "  mail goes out through $SMTP_HOST"
elif [ -n "${MAILTRAP_USERNAME:-}" ] && [ -n "${MAILTRAP_PASSWORD:-}" ] && [ -z "$current_host" ]; then
  # Sandbox only while nothing else is configured: it never replaces real mail settings
  set_env MAIL_MAILER smtp
  set_env MAIL_HOST sandbox.smtp.mailtrap.io
  set_env MAIL_PORT 2525
  set_env MAIL_USERNAME "$MAILTRAP_USERNAME"
  set_env MAIL_PASSWORD "$MAILTRAP_PASSWORD"
  set_env MAIL_ENCRYPTION null
  echo "  mail goes to the Mailtrap sandbox (set the SMTP_* secrets for real delivery)"
elif [ "$current_host" = "sandbox.smtp.mailtrap.io" ]; then
  echo "::warning::Mail still goes to the Mailtrap sandbox; set the SMTP_* GitHub secrets for real delivery."
fi

echo "$ENV_FILE ready"
