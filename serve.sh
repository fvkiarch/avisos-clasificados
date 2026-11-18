#!/usr/bin/env bash

cd "$(dirname "$0")"
PHP="${PHP:-/opt/lampp/bin/php}"
export DB_HOST="${DB_HOST:-127.0.0.1}"
trap 'kill 0' EXIT
"$PHP" -S 127.0.0.1:8099 -t backend/public backend/public/index.php &
"$PHP" -S 127.0.0.1:8080 -t frontend &
echo; echo "  Abrí  http://127.0.0.1:8080   (API en http://127.0.0.1:8099/api/v1)"; echo
wait
