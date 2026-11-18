
SET NAMES utf8mb4;
USE avisos_clasificados;

CALL sp_backup_iniciar('COMPLETO', @id_bk);
CALL sp_backup_finalizar(@id_bk, 'OK', 'avisos_2026-10-18_0200.sql.gz', 48213, REPEAT('a',64), 'Ejemplo');

SELECT id_backup, tipo, estado, fecha_inicio, fecha_fin,
       TIMESTAMPDIFF(SECOND, fecha_inicio, fecha_fin) AS segundos, archivo, tamano_bytes
FROM auditoria_backup ORDER BY fecha_inicio DESC LIMIT 10;

SELECT * FROM auditoria_backup
WHERE estado = 'ERROR'
   OR (estado = 'EN_CURSO' AND fecha_inicio < NOW() - INTERVAL 1 HOUR);

SELECT IF(COUNT(*) > 0, 'OK', 'ALERTA: sin respaldo reciente') AS estado_politica
FROM auditoria_backup
WHERE estado = 'OK' AND tipo = 'COMPLETO' AND fecha_inicio >= NOW() - INTERVAL 36 HOUR;
