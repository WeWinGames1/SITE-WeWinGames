#!/usr/bin/env bash
#
# Production deploy for WeWinGames on a git-pull checkout.
#
# Usage, on the server from the app directory:
#   ./deploy.sh                          deploy origin/main
#   DEPLOY_BRANCH=release ./deploy.sh    deploy another branch
#
# Optional environment:
#   DEPLOY_BRANCH  branch to fast-forward to                  (default: main)
#   PHP_BIN        php binary                                  (default: php)
#   COMPOSER_BIN   composer binary                             (default: composer)
#   WEB_USER       owner for storage/ and bootstrap/cache, applied only when
#                  run as root (e.g. www-data)
#   SKIP_ASSETS=1  skip the npm install/build (PHP-only hotfix)
#   SKIP_PULL=1    rebuild the current checkout without fetching (recovery/rollback)
#
# First-time setup on a new server:
#   git clone https://github.com/WeWinGames1/SITE-WeWinGames.git wewingames && cd wewingames
#   cp .env.production.example .env        # then fill in real values
#   composer install --no-dev --optimize-autoloader
#   php artisan key:generate
#   ./deploy.sh
#
# .env lives only on the server and is never tracked. storage/ is never touched
# by a pull, so uploads, logs and sessions persist; public/storage is linked to
# storage/app/public on every run.

set -Eeuo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
BRANCH="${DEPLOY_BRANCH:-main}"
PHP="${PHP_BIN:-php}"
COMPOSER="${COMPOSER_BIN:-composer}"

cd "$APP_DIR"

# Node from nvm on the production box, when present.
if [ -d /opt/nvm/versions/node/v22.17.0/bin ]; then
    export PATH="/opt/nvm/versions/node/v22.17.0/bin:$PATH"
fi

log() { printf '\n\033[1;34m==>\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m!!\033[0m %s\n' "$*" >&2; }
fail() {
    printf '\n\033[1;31mxx\033[0m %s\n' "$*" >&2
    exit 1
}

# Value of a key in .env, without quotes or a trailing comment. Never echo
# secrets with this; it exists for the non-secret sanity checks below.
env_value() {
    sed -nE "s/^$1=\"?([^\"#]*)\"?.*$/\1/p" .env | tail -n 1 | sed -E 's/[[:space:]]+$//'
}

tracked_changes() {
    git status --porcelain --untracked-files=no
}

# ---------------------------------------------------------------------------
# Preflight: everything that can fail without taking the site down.
# ---------------------------------------------------------------------------
log "Preflight checks"

[ -f .env ] || fail ".env is missing. Copy .env.production.example to .env and fill in real values."

if git ls-files --error-unmatch .env >/dev/null 2>&1; then
    fail ".env is tracked by git. Run 'git rm --cached .env', commit, and rotate every secret it contained."
fi

[ "$(env_value APP_ENV)" = "production" ] || fail "APP_ENV must be 'production' in .env (found '$(env_value APP_ENV)')."
[ "$(env_value APP_DEBUG)" = "false" ] || fail "APP_DEBUG must be 'false' in .env: debug pages expose environment secrets."

case "$(env_value APP_KEY)" in
    base64:?*) ;;
    *) fail "APP_KEY is not set. Run '$PHP artisan key:generate' once." ;;
esac

command -v "$PHP" >/dev/null || fail "php not found (set PHP_BIN)."
command -v "$COMPOSER" >/dev/null || fail "composer not found (set COMPOSER_BIN)."
if [ "${SKIP_ASSETS:-0}" != 1 ]; then
    command -v npm >/dev/null || fail "npm not found. Install Node 22 or set SKIP_ASSETS=1."
    [ -f package-lock.json ] || fail "package-lock.json is missing; npm ci needs it for a reproducible build."
fi

if [ -e public/storage ] && [ ! -L public/storage ]; then
    fail "public/storage is a real directory, not a symlink. Move its contents into storage/app/public, delete it, and re-run."
fi

if [ -n "$(tracked_changes)" ]; then
    tracked_changes >&2
    fail "Tracked files were modified on this server (above). A pull would fail on or overwrite them. Commit the change upstream, or discard it with 'git checkout -- <file>'."
fi

PREVIOUS_COMMIT="$(git rev-parse --short HEAD)"

if [ "${SKIP_PULL:-0}" != 1 ]; then
    CURRENT_BRANCH="$(git symbolic-ref --quiet --short HEAD || echo 'a detached HEAD')"
    [ "$CURRENT_BRANCH" = "$BRANCH" ] || fail "This checkout is on $CURRENT_BRANCH, not '$BRANCH'. Run 'git switch $BRANCH' or set DEPLOY_BRANCH."

    log "Fetching origin/$BRANCH"
    git fetch --prune origin "$BRANCH"

    git merge-base --is-ancestor HEAD "origin/$BRANCH" ||
        fail "origin/$BRANCH is not a fast-forward of this checkout (a commit was made on the server, or history was rewritten). Resolve by hand."
fi

# ---------------------------------------------------------------------------
# Deploy. From here on, a failure leaves the site in maintenance mode rather
# than serving code whose vendor/, assets or schema are half-updated.
# ---------------------------------------------------------------------------
IN_MAINTENANCE=0

on_exit() {
    local status=$?
    if [ "$status" -ne 0 ] && [ "$IN_MAINTENANCE" = 1 ]; then
        warn "Deploy failed (exit $status). The site is STILL IN MAINTENANCE MODE."
        warn "Fix the error, then re-run:   SKIP_PULL=1 ./deploy.sh"
        warn "Or roll back the code:        git reset --hard $PREVIOUS_COMMIT && SKIP_PULL=1 ./deploy.sh"
        warn "                              (migrations that already ran are not rolled back)"
        warn "Or bring it up as-is:         $PHP artisan up"
    fi
}
trap on_exit EXIT

log "Entering maintenance mode"
"$PHP" artisan down --retry=30 --refresh=15
IN_MAINTENANCE=1

if [ "${SKIP_PULL:-0}" != 1 ]; then
    log "Fast-forwarding to origin/$BRANCH"
    git merge --ff-only "origin/$BRANCH"
fi
log "Code is at $(git rev-parse --short HEAD) (was $PREVIOUS_COMMIT)"

log "Ensuring runtime directories exist"
mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Cached manifests can name providers from the previous vendor/. Drop them so
# composer's package:discover boots against the new code instead.
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    bootstrap/cache/config.php bootstrap/cache/routes-v7.php bootstrap/cache/events.php

log "Installing PHP dependencies"
"$COMPOSER" install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if [ "${SKIP_ASSETS:-0}" != 1 ]; then
    log "Building front-end assets"
    # npm ci installs exactly what package-lock.json pins and never rewrites it,
    # so the checkout stays clean for the next pull. --maxsockets 1 avoids the
    # EMFILE errors this server hits under concurrency.
    npm ci --maxsockets 1 --no-audit --no-fund
    npm run build
    [ -f public/build/manifest.json ] || fail "Vite finished without writing public/build/manifest.json."
fi

log "Linking public/storage -> storage/app/public"
if [ -L public/storage ] && [ "$(cd public/storage 2>/dev/null && pwd -P)" != "$(cd storage/app/public && pwd -P)" ]; then
    warn "public/storage pointed somewhere else or was dangling; recreating it."
    rm public/storage
fi
[ -L public/storage ] || "$PHP" artisan storage:link

log "Running migrations"
"$PHP" artisan migrate --force

log "Caching config, routes, views and events"
"$PHP" artisan optimize

log "Setting permissions on storage/ and bootstrap/cache"
if [ -n "${WEB_USER:-}" ]; then
    if [ "$(id -u)" = 0 ]; then
        chown -R "$WEB_USER":"$WEB_USER" storage bootstrap/cache
    else
        warn "WEB_USER is set but this is not running as root; skipping chown."
    fi
fi
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null ||
    warn "Could not chmod everything under storage/ or bootstrap/cache; check file ownership."

log "Signalling queue workers to restart"
"$PHP" artisan queue:restart

log "Leaving maintenance mode"
"$PHP" artisan up
IN_MAINTENANCE=0

if [ -n "$(tracked_changes)" ]; then
    tracked_changes >&2
    warn "This deploy modified tracked files (above). The next deploy will refuse to run until they are reverted."
fi

log "Deployed $(git rev-parse --short HEAD)"
