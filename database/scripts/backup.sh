#!/usr/bin/env bash

set -euo pipefail

DB="avisos_clasificados"
DEST="${BACKUP_DIR:-./backups/avisos}"
RETENCION_DIAS=30
CONFIG="$HOME/.my.cnf"
MYSQL_BIN="${MYSQL_BIN:-$(command -v mariadb || command -v mysql || true)}"
MYSQLDUMP_BIN="${MYSQLDUMP_BIN:-$(command -v mariadb-dump || command -v mysqldump || true)}"
FECHA=$(date +%F_%H%M)
ARCHIVO="$DEST/${DB}_${FECHA}.sql.gz"

if [[ ! -f "$CONFIG" ]]; then
    echo "No existe .my.cnf" >&2
    exit 2
fi

if [[ -z "$MYSQL_BIN" || -z "$MYSQLDUMP_BIN" ]]; then
    echo "No se encontró el cliente." >&2
    exit 2
fi


MYSQL=("$MYSQL_BIN" "--defaults-file=$CONFIG" -N -B -D "$DB")

mkdir -p "$DEST"
chmod 700 "$DEST"


if ! ID=$("${MYSQL[@]}" -e "CALL sp_backup_iniciar('COMPLETO', @id); SELECT @id;"); then
    echo "No se pudo conectar a la base." >&2
    echo "  - Capaz que no está corriendo MariaDB/MySQL" >&2
    echo "  - O capaz que no está bien configurado el .my.cnf" >&2
    exit 1
fi


falla() {
    "${MYSQL[@]}" -e "CALL sp_backup_finalizar($ID,'ERROR',NULL,NULL,NULL,'Fallo en línea $1')" || true
    echo "ERROR: el respaldo falló (línea $1). Quedó registrado en auditoria_backup." >&2
    exit 1
}
trap 'falla $LINENO' ERR


"$MYSQLDUMP_BIN" --defaults-file="$CONFIG" --single-transaction --routines --triggers --events --no-tablespaces "$DB" | gzip -9 > "$ARCHIVO"

gzip -t "$ARCHIVO"                                  
TAM=$(stat -c%s "$ARCHIVO")
SHA=$(sha256sum "$ARCHIVO" | cut -d' ' -f1)
echo "$SHA  $(basename "$ARCHIVO")" > "$ARCHIVO.sha256"

"${MYSQL[@]}" -e "CALL sp_backup_finalizar($ID,'OK','$(basename "$ARCHIVO")',$TAM,'$SHA','Retención ${RETENCION_DIAS} días')"


find "$DEST" -name "${DB}_*.sql.gz*" -mtime +"$RETENCION_DIAS" -delete

echo "[$(date '+%F %T')] Respaldo OK: $ARCHIVO ($TAM bytes)"
