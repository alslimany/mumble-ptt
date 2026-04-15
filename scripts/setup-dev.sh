#!/usr/bin/env bash
# setup-dev.sh – Bootstrap the development environment
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND="$ROOT/backend"

echo "==> Copying .env.example to .env"
[ -f "$BACKEND/.env" ] || cp "$BACKEND/.env.example" "$BACKEND/.env"

echo "==> Building and starting Docker containers"
docker compose -f "$ROOT/docker/docker-compose.yml" up -d --build

echo "==> Waiting for PostgreSQL to be ready..."
sleep 5

echo "==> Running Laravel migrations and seeders"
docker exec ptt-laravel php artisan migrate --seed --force

echo "==> Generating application key"
docker exec ptt-laravel php artisan key:generate --force

echo "==> Creating storage symlink"
docker exec ptt-laravel php artisan storage:link

echo "==> Done! Admin dashboard: http://localhost"
echo "    Default credentials: admin@example.com / password"
