#!/bin/bash

publicdir=/var/www/html/app/storage/app/public/files
privatedir=/var/www/html/app/storage/app/private/files
publiccrondir=$(date "+$publicdir/%Y-%m")
privatecrondir=$(date "+$privatedir/%Y-%m")

mkdir -p "$publiccrondir"
chmod -R 755 "$publiccrondir"
chown -R www-data:www-data "$publiccrondir"

mkdir -p "$privatecrondir"
chmod -R 755 "$privatecrondir"
chown -R www-data:www-data "$privatecrondir"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] New folder has been created at '$publiccrondir'" >> /var/www/html/app/storage/logs/bash.log
echo "[$(date '+%Y-%m-%d %H:%M:%S')] New folder has been created at '$privatecrondir'" >> /var/www/html/app/storage/logs/bash.log
