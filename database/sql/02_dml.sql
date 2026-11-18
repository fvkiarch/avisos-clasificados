SET NAMES utf8mb4;   -- doble acento cuando intento importar no sé pq
USE avisos_clasificados;
SET time_zone = '+00:00';

INSERT INTO rol (nombre, descripcion) VALUES
 ('administrador','Control total del sistema'),
 ('publicador','Publica y gestiona sus propios avisos'),
 ('visitante','Solo lectura pública'),
 ('respaldo','Ejecuta y audita copias de seguridad');

INSERT INTO permiso (codigo, descripcion) VALUES
 ('listing.view','Ver avisos'),
 ('listing.create','Publicar avisos'),
 ('listing.highlight','Marcar avisos como resaltados'),
 ('listing.delete_own','Eliminar avisos propios'),
 ('listing.delete_any','Eliminar cualquier aviso'),
 ('author.manage','Alta, baja y modificación de autores'),
 ('role.manage','Gestionar roles y permisos'),
 ('backup.run','Ejecutar respaldos'),
 ('backup.audit','Consultar auditoría de respaldos');

INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso FROM rol r JOIN permiso p ON
  (r.nombre='administrador' AND p.codigo IN ('listing.view','listing.create','listing.highlight','listing.delete_own',
        'listing.delete_any','author.manage','role.manage','backup.audit'))
  OR (r.nombre='publicador' AND p.codigo IN ('listing.view','listing.create','listing.delete_own'))
  OR (r.nombre='visitante'  AND p.codigo IN ('listing.view'))
  OR (r.nombre='respaldo'   AND p.codigo IN ('backup.run','backup.audit'));

INSERT INTO categoria (nombre) VALUES ('Laboral'),('Producto'),('Inmobiliario');
INSERT INTO tipo_inmueble (nombre) VALUES ('Casa'),('Apartamento'),('Terreno'),('Local comercial');

INSERT INTO autor (nombre, email, telefono, password_hash, id_rol) VALUES
 ('Administrador','admin@avisos.test','099111111','$2y$10$ea0zZDG9qqkq43wUIoEFQ.cjMwLj4Az/PnWaRbKdqIovd/GEUmO0S',1),
 ('Lucía Pereira','lucia@avisos.test','099222222','$2y$10$ea0zZDG9qqkq43wUIoEFQ.cjMwLj4Az/PnWaRbKdqIovd/GEUmO0S',2),
 ('Martín Rodríguez','martin@avisos.test','099333333','$2y$10$ea0zZDG9qqkq43wUIoEFQ.cjMwLj4Az/PnWaRbKdqIovd/GEUmO0S',2),
 ('Carla Núñez','carla@avisos.test',NULL,'$2y$10$ea0zZDG9qqkq43wUIoEFQ.cjMwLj4Az/PnWaRbKdqIovd/GEUmO0S',3),
 ('Bot Respaldo','respaldo@avisos.test',NULL,'$2y$10$ea0zZDG9qqkq43wUIoEFQ.cjMwLj4Az/PnWaRbKdqIovd/GEUmO0S',4);

SET @id = 0;
CALL sp_publicar_laboral(2,'Se busca desarrollador Rust',0,'Canonical','Desarrollo y colaboración de kernel.',85000.00,@id);
CALL sp_publicar_laboral(3,'Técnico en redes',0,'RedHat','Soporte de infraestructura, cableado y switching.',NULL,@id);
CALL sp_publicar_producto(2,'Notebook 14" usada',0,'Notebook i9, 128GB RAM, 10TB SSD. Excelente estado.',18500.00,@id);
CALL sp_publicar_producto(3,'Bicicleta rodado 29',0,'Bicicleta de montaña, 21 velocidades.',9800.50,@id);
CALL sp_publicar_inmobiliario(2,'Apartamento 2 dormitorios',0,2,'Punta Gorda, Montevideo',58.50,165000.00,@id);
CALL sp_publicar_inmobiliario(3,'Terreno en Ciudad de la Costa',0,3,'Lagomar, Canelones',420.00,72000.00,@id);

CALL sp_publicar_producto(1,'Oferta: Monitor 34" 2k IPS',1,'Monitor IPS 175Hz, caja sellada.',7200.00,@id);

UPDATE aviso SET resaltado = 1 WHERE titulo = 'Se busca desarrollador PHP';
UPDATE aviso_producto SET precio = precio * 0.90
 WHERE id_aviso = (SELECT id_aviso FROM aviso WHERE titulo = 'Bicicleta rodado 29');
UPDATE autor SET telefono = '099444444' WHERE email = 'carla@avisos.test';

DELETE FROM aviso WHERE titulo = 'Bicicleta rodado 29';

-- ---- Pruebas de integridad (si alguna de estas no falla es pq hice algo mal) ----
-- DELETE FROM autor WHERE email = 'lucia@avisos.test';        -- RESTRICT: tiene avisos
-- DELETE FROM categoria WHERE nombre = 'Laboral';             -- RESTRICT: tiene avisos
-- INSERT INTO autor (nombre,email,id_rol,password_hash) VALUES ('Dup','admin@avisos.test',1,'x'); -- email único
-- INSERT INTO aviso_laboral VALUES (3,'X','Y',1000);          -- trigger: aviso 3 no es Laboral
-- UPDATE aviso SET id_categoria = 1 WHERE id_aviso = 3;       -- trigger: categoría inmutable
-- CALL sp_publicar_producto(4,'Intento visitante',0,'x',10,@id); -- sin permiso listing.create
