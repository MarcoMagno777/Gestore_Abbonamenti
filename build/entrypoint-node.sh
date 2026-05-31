#!/bin/sh
set -e

cd /app

npm install

exec npx ng serve --host 0.0.0.0 --port 4200