#!/bin/bash
set -e

cd /app

if [ ! -x node_modules/.bin/vite ] && [ ! -d node_modules ]; then
  npm install --no-package-lock
fi

exec npm run start -- --host 0.0.0.0
