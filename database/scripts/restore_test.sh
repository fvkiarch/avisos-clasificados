#!/usr/bin/env bash

set -euo pipefail

DEST="${BACKUP_DIR:-./backups/avisos}"

if [[ -S /opt/lampp/var/mysql/mysql.sock ]]; then
    MYSQL_BIN="${MYSQL_BIN:-/opt/lampp/bin/mysql}"
    MYSQL_ARGS="${MYSQL_ARGS:---no-defaults -uroot --socket=/opt/lampp/var/mysql/mysql.sock}"
else
    MYSQL_BIN="${MYSQL_BIN:-$(command -v mariadb || command -v mysql)}"
    MYSQL_ARGS="${MYSQL_ARGS:-}"
fi

ULTIMO=$(ls -1t "$DEST"/*.sql.gz | head -1)


(cd "$DEST" && sha256sum -c "$(basename "$ULTIMO").sha256")


$MYSQL_BIN $MYSQL_ARGS -e "DROP DATABASE IF EXISTS avisos_restore_test; CREATE DATABASE avisos_restore_test CHARACTER SET utf8mb4;"
gunzip -c "$ULTIMO" | sed 's/`avisos_clasificados`/`avisos_restore_test`/g' | $MYSQL_BIN $MYSQL_ARGS avisos_restore_test
$MYSQL_BIN $MYSQL_ARGS -e "SELECT (SELECT COUNT(*) FROM avisos_restore_test.aviso) AS avisos_restaurados,
                                 (SELECT COUNT(*) FROM avisos_clasificados.aviso) AS avisos_origen;"
$MYSQL_BIN $MYSQL_ARGS -e "DROP DATABASE avisos_restore_test;"

echo "Prueba de restauración OK con $ULTIMO"
