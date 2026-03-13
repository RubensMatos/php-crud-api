#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="8089"
TMP_DB="$(mktemp /tmp/php-crud-api.XXXXXX.sqlite)"

cleanup() {
  if [[ -n "${SERVER_PID:-}" ]]; then
    kill "$SERVER_PID" >/dev/null 2>&1 || true
  fi
  rm -f "$TMP_DB"
}

trap cleanup EXIT

(
  cd "$ROOT_DIR"
  DB_PATH="$TMP_DB" php -S "127.0.0.1:${PORT}" -t public public/index.php >/tmp/php-crud-api-smoke.log 2>&1
) &
SERVER_PID=$!

sleep 1

health_response="$(curl -s "http://127.0.0.1:${PORT}/health")"
if ! printf '%s' "$health_response" | grep -q '"success":true'; then
  echo "Health check falhou"
  exit 1
fi

create_response="$(curl -s -X POST "http://127.0.0.1:${PORT}/api/tasks" -H 'Content-Type: application/json' -d '{"title":"Preparar demo","description":"Criar showcase profissional"}')"
if ! printf '%s' "$create_response" | grep -q '"success":true'; then
  echo "Criacao falhou"
  exit 1
fi

task_id="$(php -r '$p=json_decode($argv[1], true); echo $p["data"]["id"] ?? "";' "$create_response")"
if [[ -z "$task_id" ]]; then
  echo "Nao foi possivel obter ID da task"
  exit 1
fi

list_response="$(curl -s "http://127.0.0.1:${PORT}/api/tasks")"
if ! printf '%s' "$list_response" | grep -q '"count":1'; then
  echo "Listagem falhou"
  exit 1
fi

update_response="$(curl -s -X PUT "http://127.0.0.1:${PORT}/api/tasks/${task_id}" -H 'Content-Type: application/json' -d '{"status":"done"}')"
if ! printf '%s' "$update_response" | grep -q '"status":"done"'; then
  echo "Atualizacao falhou"
  exit 1
fi

delete_response="$(curl -s -X DELETE "http://127.0.0.1:${PORT}/api/tasks/${task_id}")"
if ! printf '%s' "$delete_response" | grep -q '"success":true'; then
  echo "Remocao falhou"
  exit 1
fi

echo "Smoke test concluido com sucesso."
