#!/usr/bin/env bash
# Deploy LinkOut to the didudi Hetzner box.
set -euo pipefail
HOST=root@debian-2gb-didudi
DEST=/var/www/linkout

rsync -avz --delete \
  --exclude .git --exclude vendor --exclude config.local.php --exclude '.DS_Store' \
  ./ "$HOST:$DEST/"

ssh "$HOST" "cd $DEST \
  && php bin/migrate.php \
  && chown -R www-data:www-data $DEST \
  && systemctl reload php8.4-fpm nginx"
echo "deployed: https://linkout.didudi.com"
