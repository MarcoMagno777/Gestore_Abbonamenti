#!/bin/sh
set -e

cd /var/www/html

if [ -f composer.json ] && [ ! -d vendor ]; then
  composer install
fi

exec apache2-foreground
