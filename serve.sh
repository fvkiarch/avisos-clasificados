#!/usr/bin/env bash

cd "$(dirname "$0")"
PHP="${PHP:-/opt/lampp/bin/php}"
export DB_HOST="${DB_HOST:-127.0.0.1}"

echo "[1/3] Verificando e inicializando la base de datos..."
"$PHP" scripts/init_db.php
if [ $? -ne 0 ]; then
    echo "[ERROR] No se pudo inicializar la base de datos."
    exit 1
fi

trap 'kill 0' EXIT
"$PHP" -S 127.0.0.1:8099 -t backend/public backend/public/index.php &
"$PHP" -S 127.0.0.1:8080 -t frontend &
echo; echo "  Abrí  http://127.0.0.1:8080   (API en http://127.0.0.1:8099/api/v1)"; echo
wait
