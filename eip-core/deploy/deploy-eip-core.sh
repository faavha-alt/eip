#!/bin/bash
# Redeploy eip-core dari GitHub (faavha-alt/eip, branch master, subfolder eip-core/).
# htdocs/eip.mipa.uns.ac.id/ adalah docroot tetap (di-set panel CloudPanel),
# jadi kita sync isi eip-core/ ke situ, bukan git pull langsung di docroot.
set -euo pipefail

export PATH="$HOME/node/bin:$PATH"

REPO="$HOME/repo"
SITE="$HOME/htdocs/eip.mipa.uns.ac.id"

cd "$REPO"
git fetch origin
git reset --hard origin/master

rsync -a --delete \
  --exclude='.env' \
  --exclude='.git' \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='public/build' \
  --exclude='storage/logs' \
  --exclude='storage/framework/cache' \
  --exclude='storage/framework/sessions' \
  --exclude='storage/framework/views' \
  --exclude='database/database.sqlite' \
  "$REPO/eip-core/" "$SITE/"

cd "$SITE"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

COMMIT="$(git -C "$REPO" rev-parse --short HEAD)"
echo "Deploy selesai: ${COMMIT}"
