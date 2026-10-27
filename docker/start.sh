#!/usr/bin/env bash
set -Eeuo pipefail
cd /app
umask 0027

# Defense against linking this Railway service to a development branch by mistake.
# Git metadata must come from Railway's GitHub trigger, not a manually spoofed variable.
if [[ -n "${RAILWAY_PROJECT_ID:-}" || -n "${RAILWAY_GIT_BRANCH:-}" ]]; then
    [[ "${RAILWAY_GIT_BRANCH:-}" == produccion ]] || {
        echo 'Railway startup requires the produccion GitHub branch' >&2
        exit 1
    }
fi

# Fail closed rather than booting with a dev environment or generating keys.
[[ "${APP_ENV:-}" == production ]] || { echo 'APP_ENV must be production' >&2; exit 1; }
[[ "${APP_DEBUG:-false}" == false ]] || { echo 'APP_DEBUG must be false' >&2; exit 1; }
for variable in APP_KEY APP_URL DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD; do
    [[ -n "${!variable:-}" ]] || { echo "Required variable missing: $variable" >&2; exit 1; }
done
[[ "$APP_URL" == https://* ]] || { echo 'APP_URL must use HTTPS' >&2; exit 1; }
[[ "${DB_CONNECTION:-mysql}" == mysql ]] || { echo 'DB_CONNECTION must be mysql' >&2; exit 1; }

# Runs after Railway mounts /app/storage. Never clear sessions, files, logs or DB.
directories=(storage storage/app storage/app/public storage/framework
    storage/framework/cache storage/framework/cache/data storage/framework/sessions
    storage/framework/views storage/logs storage/fonts bootstrap/cache)
mkdir -p "${directories[@]}"
chown www-data:www-data "${directories[@]}"
chmod 750 "${directories[@]}"

# Discovery was deliberately not run by Composer in the image build.
gosu www-data php artisan package:discover --ansi
gosu www-data php artisan config:cache
gosu www-data php artisan route:cache
gosu www-data php artisan view:cache
gosu www-data php artisan permission:cache-reset
if [[ ! -e public/storage && ! -L public/storage ]]; then
    ln -s /app/storage/app/public public/storage
fi

php-fpm -tt
caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile

fpm_pid=''
caddy_pid=''
shutdown() {
    trap - TERM INT
    [[ -z "$caddy_pid" ]] || kill -TERM "$caddy_pid" 2>/dev/null || true
    [[ -z "$fpm_pid" ]] || kill -QUIT "$fpm_pid" 2>/dev/null || true
    [[ -z "$caddy_pid" ]] || wait "$caddy_pid" 2>/dev/null || true
    [[ -z "$fpm_pid" ]] || wait "$fpm_pid" 2>/dev/null || true
}
trap 'shutdown; exit 0' TERM INT
php-fpm -F &
fpm_pid=$!
gosu www-data caddy run --config /etc/caddy/Caddyfile --adapter caddyfile &
caddy_pid=$!
set +e
wait -n "$fpm_pid" "$caddy_pid"
set -e
echo 'An essential web process exited; stopping container' >&2
shutdown
exit 1
