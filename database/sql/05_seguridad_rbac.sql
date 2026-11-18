SET NAMES utf8mb4;   -- doble acento cuando importo estos scripts
USE avisos_clasificados;

DROP USER IF EXISTS 'app_admin'@'localhost';
DROP USER IF EXISTS 'app_publicador'@'localhost';
DROP USER IF EXISTS 'app_visitante'@'localhost';
DROP USER IF EXISTS 'app_backup'@'localhost';

CREATE USER 'app_admin'@'localhost'      IDENTIFIED BY 'admin123';
CREATE USER 'app_publicador'@'localhost' IDENTIFIED BY 'publicador123';
CREATE USER 'app_visitante'@'localhost'  IDENTIFIED BY 'visitante123';
CREATE USER 'app_backup'@'localhost'   IDENTIFIED BY 'respaldo123';


GRANT ALL PRIVILEGES ON avisos_clasificados.* TO 'app_admin'@'localhost';


GRANT SELECT ON avisos_clasificados.v_avisos_publicos TO 'app_publicador'@'localhost';
GRANT SELECT ON avisos_clasificados.categoria         TO 'app_publicador'@'localhost';
GRANT SELECT ON avisos_clasificados.tipo_inmueble     TO 'app_publicador'@'localhost';
GRANT EXECUTE ON PROCEDURE avisos_clasificados.sp_publicar_laboral      TO 'app_publicador'@'localhost';
GRANT EXECUTE ON PROCEDURE avisos_clasificados.sp_publicar_producto     TO 'app_publicador'@'localhost';
GRANT EXECUTE ON PROCEDURE avisos_clasificados.sp_publicar_inmobiliario TO 'app_publicador'@'localhost';
GRANT EXECUTE ON PROCEDURE avisos_clasificados.sp_eliminar_aviso        TO 'app_publicador'@'localhost';

GRANT SELECT ON avisos_clasificados.v_avisos_publicos TO 'app_visitante'@'localhost';
GRANT SELECT ON avisos_clasificados.categoria         TO 'app_visitante'@'localhost';
GRANT SELECT ON avisos_clasificados.tipo_inmueble     TO 'app_visitante'@'localhost';
GRANT EXECUTE ON PROCEDURE avisos_clasificados.sp_datos_login    TO 'app_visitante'@'localhost';
GRANT EXECUTE ON PROCEDURE avisos_clasificados.sp_permisos_autor TO 'app_visitante'@'localhost';


GRANT SELECT, SHOW VIEW, TRIGGER, LOCK TABLES, EVENT ON avisos_clasificados.* TO 'app_backup'@'localhost';
GRANT EXECUTE ON PROCEDURE avisos_clasificados.sp_backup_iniciar   TO 'app_backup'@'localhost';
GRANT EXECUTE ON PROCEDURE avisos_clasificados.sp_backup_finalizar TO 'app_backup'@'localhost';

GRANT SELECT ON mysql.proc TO 'app_backup'@'localhost';

FLUSH PRIVILEGES;

SHOW GRANTS FOR 'app_admin'@'localhost';
SHOW GRANTS FOR 'app_publicador'@'localhost';
SHOW GRANTS FOR 'app_visitante'@'localhost';
SHOW GRANTS FOR 'app_backup'@'localhost';

-- ---- Pruebas (estas cosas tienen que fallar) ----
-- app_visitante:   SELECT * FROM v_avisos_publicos;       -- OK
-- app_visitante:   SELECT * FROM autor;                   -- ERROR 1142 (sin SELECT)
-- app_publicador:  DELETE FROM aviso;                     -- ERROR 1142 (sin DELETE directo)
-- app_publicador:  CALL sp_eliminar_aviso(1, 3);          -- ERROR 45000 (aviso ajeno)
-- app_backup:    UPDATE aviso SET resaltado = 1;          -- ERROR 1142
