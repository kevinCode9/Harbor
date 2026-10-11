#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# LivePanel — cPanel post-upload setup script
# Run once from cPanel Terminal after uploading files:
#   bash ~/liveled/cpanel-setup.sh
# ─────────────────────────────────────────────────────────────────────────────

set -e
APP_DIR="$(cd "$(dirname "$0")" && pwd)"
echo "► Working in: $APP_DIR"

# 1. Install PHP dependencies (no dev, optimised autoloader)
echo "► Installing Composer dependencies..."
cd "$APP_DIR"
composer install --no-dev --optimize-autoloader --no-interaction --quiet

# 2. Copy .env if not already present
if [ ! -f "$APP_DIR/.env" ]; then
  cp "$APP_DIR/.env.production" "$APP_DIR/.env"
  echo "► Copied .env.production → .env  (edit it now!)"
else
  echo "► .env already exists — skipping copy"
fi

# 3. Generate app key if empty
KEY=$(grep "^APP_KEY=" "$APP_DIR/.env" | cut -d= -f2 | tr -d ' ')
if [ -z "$KEY" ] || [ "$KEY" = '""' ]; then
  php artisan key:generate --force
  echo "► App key generated"
fi

# 4. Create SQLite database file if missing
DB_FILE="$APP_DIR/database/database.sqlite"
if [ ! -f "$DB_FILE" ]; then
  touch "$DB_FILE"
  echo "► Created $DB_FILE"
fi

# 5. Run migrations
echo "► Running migrations..."
php artisan migrate --force --quiet

# 6. Set storage / bootstrap/cache permissions
echo "► Setting permissions..."
chmod -R 775 "$APP_DIR/storage"
chmod -R 775 "$APP_DIR/bootstrap/cache"
chmod 664 "$DB_FILE"

# 7. Cache config, routes, views for performance
echo "► Caching config / routes / views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "✓ Setup complete. Make sure public_html/index.php points to this directory."
echo "  See deployment guide for document-root wiring instructions."
