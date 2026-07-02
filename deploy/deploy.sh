#!/bin/bash
# =============================================================================
# RestoApp — Production Deployment Script
# =============================================================================
# Usage: bash deploy/deploy.sh
# Run from project root: /var/www/resto
# =============================================================================

set -e

APP_DIR="/var/www/resto"
BRANCH="main"

echo "🚀 RestoApp Deployment started at $(date)"
echo "=================================================="

# 1. Pull latest code
echo "📦 Pulling latest code from $BRANCH..."
cd "$APP_DIR"
git fetch origin
git checkout $BRANCH
git pull origin $BRANCH

# 2. Install dependencies (no dev)
echo "📥 Installing Composer dependencies (production)..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Install & build frontend
echo "🎨 Building frontend assets..."
npm ci --silent
npm run build

# 4. Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# 5. Seed system settings (if needed)
echo "🌱 Seeding default system settings..."
php artisan db:seed --class=SystemSettingSeeder --force 2>/dev/null || true

# 6. Clear & rebuild caches
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# 7. Create storage symlink
echo "🔗 Creating storage link..."
php artisan storage:link 2>/dev/null || true

# 8. Restart queue workers
echo "🔄 Restarting queue workers..."
php artisan queue:restart

# 9. Restart Reverb WebSocket server
echo "📡 Restarting Reverb..."
supervisorctl restart resto-reverb 2>/dev/null || true
supervisorctl restart resto-queue-worker:* 2>/dev/null || true

# 10. Set permissions
echo "🔒 Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "=================================================="
echo "✅ Deployment completed at $(date)"
echo "🌐 App: https://resto.app"
echo "📡 Reverb: wss://resto.app:8080"
