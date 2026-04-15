#!/usr/bin/env bash
# test-e2e.sh – Run end-to-end integration tests
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "==> Running Laravel feature tests"
docker exec ptt-laravel php artisan test --parallel

echo "==> Running API smoke tests (curl)"
BASE_URL="${BASE_URL:-http://localhost}"
curl -sf "$BASE_URL/api/v1/health" | jq .

echo "==> E2E tests passed"
