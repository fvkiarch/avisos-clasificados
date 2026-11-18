SET NAMES utf8mb4;
USE avisos_clasificados;

START TRANSACTION;
  INSERT INTO aviso (titulo, resaltado, id_autor, id_categoria)
    VALUES ('Vendo escritorio de madera', 0, 2, 2);
  SET @nuevo = LAST_INSERT_ID();
  INSERT INTO aviso_producto (id_aviso, descripcion_producto, precio)
    VALUES (@nuevo, 'Escritorio 120x60 cm, roble.', 3500.00);
COMMIT;

START TRANSACTION;
  INSERT INTO aviso (titulo, id_autor, id_categoria) VALUES ('Aviso de prueba', 2, 1);
  SELECT COUNT(*) AS avisos_dentro_de_la_transaccion FROM aviso;
ROLLBACK;
SELECT COUNT(*) AS avisos_luego_del_rollback FROM aviso;

START TRANSACTION;
  UPDATE aviso SET resaltado = 1 WHERE id_aviso = 1;
  SAVEPOINT despues_del_resaltado;
  UPDATE aviso_laboral SET sueldo_mensual = 90000 WHERE id_aviso = 1;
  ROLLBACK TO SAVEPOINT despues_del_resaltado;
  RELEASE SAVEPOINT despues_del_resaltado;
COMMIT;

CALL sp_publicar_producto(2, 'Producto inválido', 0, 'Precio negativo', -5.00, @id);
SELECT COUNT(*) AS tiene_que_ser_0 FROM aviso WHERE titulo = 'Producto inválido';

SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ;
