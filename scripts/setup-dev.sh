#!/usr/bin/env bash
# setup-dev.sh – Bootstrap the development environment
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND="$ROOT/backend"
COMPOSE_FILE="$ROOT/docker/docker-compose.yml"

wait_for_postgres() {
	local attempt=1
	local max_attempts=30

	while [ "$attempt" -le "$max_attempts" ]; do
		if docker exec ptt-postgres pg_isready -U ptt -d ptt >/dev/null 2>&1; then
			return 0
		fi

		sleep 2
		attempt=$((attempt + 1))
	done

	echo "PostgreSQL did not become ready in time" >&2
	return 1
}

echo "==> Copying .env.example to .env"
[ -f "$BACKEND/.env" ] || cp "$BACKEND/.env.example" "$BACKEND/.env"

echo "==> Building and starting Docker containers"
docker compose -f "$COMPOSE_FILE" up -d --build --remove-orphans

echo "==> Waiting for PostgreSQL to be ready..."
wait_for_postgres

echo "==> Running Laravel migrations and seeders"
docker exec ptt-laravel php artisan migrate --seed --force

echo "==> Generating application key"
docker exec ptt-laravel php artisan key:generate --force

echo "==> Clearing Laravel caches"
docker exec ptt-laravel php artisan optimize:clear

echo "==> Creating storage symlink"
docker exec ptt-laravel php artisan storage:link --force

echo "==> Done! Admin dashboard: http://localhost"
echo "    Default credentials: admin@example.com / password"
