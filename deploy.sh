#!/usr/bin/env bash
# Deploy the server part to a host where the app directory lives outside the
# web root (e.g. CloudPanel):
#
#   $APP_DIR/            lib/, templates/, config.php, data/share.db
#   $WEB_ROOT/index.php  copy of server/public/index.php with BASE pointed at $APP_DIR
#
# config.php and data/ are never touched. A timestamped backup of the previous
# lib/, templates/ and index.php is kept in $BACKUP_DIR.
#
# Usage: ./deploy.sh [ssh-host]

set -euo pipefail

HOST="${1:-myserver}"
SITE_USER="rcwalter-opencode"
APP_DIR="/home/$SITE_USER/opencode-share"
WEB_ROOT="/home/$SITE_USER/htdocs/opencode.rcwalter.net"
BACKUP_DIR="/home/$SITE_USER/backups/opencode-share"

cd "$(dirname "$0")"

stamp=$(date +%Y%m%d-%H%M%S)
echo "▶ backing up current deployment to $BACKUP_DIR/$stamp"
ssh "$HOST" "mkdir -p '$BACKUP_DIR/$stamp' \
  && cp -a '$APP_DIR/lib' '$APP_DIR/templates' '$APP_DIR/config.php' '$BACKUP_DIR/$stamp/' \
  && cp -a '$WEB_ROOT/index.php' '$BACKUP_DIR/$stamp/' \
  && cp -a '$APP_DIR/data/share.db' '$BACKUP_DIR/$stamp/share.db'"

echo "▶ syncing lib/ and templates/"
rsync -az --delete server/app/lib/ "$HOST:$APP_DIR/lib/"
rsync -az --delete server/app/templates/ "$HOST:$APP_DIR/templates/"
rsync -az server/app/config.example.php "$HOST:$APP_DIR/config.example.php"

echo "▶ installing index.php"
sed "s#^define('BASE', .*#define('BASE', '$APP_DIR');#" server/public/index.php \
  | ssh "$HOST" "cat > '$WEB_ROOT/index.php'"

echo "▶ fixing ownership and permissions"
ssh "$HOST" "chown -R '$SITE_USER:$SITE_USER' '$APP_DIR/lib' '$APP_DIR/templates' '$APP_DIR/config.example.php' '$WEB_ROOT/index.php' \
  && chmod 640 '$APP_DIR/config.php' && chmod 750 '$APP_DIR/data' \
  && php -l '$WEB_ROOT/index.php' >/dev/null && grep -q \"define('BASE', '$APP_DIR')\" '$WEB_ROOT/index.php'"

echo "✓ deployed. Old versions: $BACKUP_DIR/$stamp"
