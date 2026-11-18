SET NAMES utf8mb4;   -- si no pongo esto me rompe las pelotas con los acentos cuando importo
DROP DATABASE IF EXISTS avisos_clasificados;
CREATE DATABASE avisos_clasificados CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE avisos_clasificados;


CREATE TABLE rol (
  id_rol       TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre       VARCHAR(30)  NOT NULL,
  descripcion  VARCHAR(150) NOT NULL,
  CONSTRAINT pk_rol PRIMARY KEY (id_rol),
  CONSTRAINT uq_rol_nombre UNIQUE (nombre)
);

CREATE TABLE permiso (
  id_permiso   SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo       VARCHAR(50)  NOT NULL,
  descripcion  VARCHAR(150) NOT NULL,
  CONSTRAINT pk_permiso PRIMARY KEY (id_permiso),
  CONSTRAINT uq_permiso_codigo UNIQUE (codigo)
);

CREATE TABLE rol_permiso (
  id_rol      TINYINT UNSIGNED  NOT NULL,
  id_permiso  SMALLINT UNSIGNED NOT NULL,
  CONSTRAINT pk_rol_permiso PRIMARY KEY (id_rol, id_permiso),
  CONSTRAINT fk_rp_rol FOREIGN KEY (id_rol) REFERENCES rol (id_rol) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_rp_permiso FOREIGN KEY (id_permiso) REFERENCES permiso (id_permiso) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE autor (
  id_autor       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre         VARCHAR(100) NOT NULL,
  email          VARCHAR(120) NOT NULL,
  telefono       VARCHAR(20)  NULL,
  password_hash  VARCHAR(255) NOT NULL,
  id_rol         TINYINT UNSIGNED NOT NULL,
  activo         TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT pk_autor PRIMARY KEY (id_autor),
  CONSTRAINT uq_autor_email UNIQUE (email),
  CONSTRAINT ck_autor_email CHECK (email LIKE '_%@_%._%'),
  CONSTRAINT ck_autor_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) > 0),
  CONSTRAINT fk_autor_rol FOREIGN KEY (id_rol) REFERENCES rol (id_rol) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE categoria (
  id_categoria  TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre        VARCHAR(20) NOT NULL,
  CONSTRAINT pk_categoria PRIMARY KEY (id_categoria),
  CONSTRAINT uq_categoria_nombre UNIQUE (nombre),
  CONSTRAINT ck_categoria_nombre CHECK (nombre IN ('Laboral','Producto','Inmobiliario'))
);

CREATE TABLE aviso (
  id_aviso        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo          VARCHAR(150) NOT NULL,
  fecha_creacion  DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  resaltado       TINYINT(1)   NOT NULL DEFAULT 0,
  id_autor        INT UNSIGNED NOT NULL,
  id_categoria    TINYINT UNSIGNED NOT NULL,
  CONSTRAINT pk_aviso PRIMARY KEY (id_aviso),
  CONSTRAINT ck_aviso_titulo CHECK (CHAR_LENGTH(TRIM(titulo)) > 0),
  CONSTRAINT ck_aviso_resaltado CHECK (resaltado IN (0,1)),
  CONSTRAINT fk_aviso_autor FOREIGN KEY (id_autor) REFERENCES autor (id_autor) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_aviso_categoria FOREIGN KEY (id_categoria) REFERENCES categoria (id_categoria) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_aviso_orden (resaltado, fecha_creacion, id_aviso),
  INDEX idx_aviso_cat_fecha (id_categoria, fecha_creacion)
);

CREATE TABLE aviso_laboral (
  id_aviso          INT UNSIGNED NOT NULL,
  empresa           VARCHAR(100) NOT NULL,
  descripcion_cargo TEXT NOT NULL,
  sueldo_mensual    DECIMAL(10,2) NULL,
  CONSTRAINT pk_aviso_laboral PRIMARY KEY (id_aviso),
  CONSTRAINT ck_laboral_sueldo CHECK (sueldo_mensual IS NULL OR sueldo_mensual > 0),
  CONSTRAINT fk_laboral_aviso FOREIGN KEY (id_aviso) REFERENCES aviso (id_aviso) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE aviso_producto (
  id_aviso              INT UNSIGNED NOT NULL,
  descripcion_producto  TEXT NOT NULL,
  precio                DECIMAL(12,2) NOT NULL,
  CONSTRAINT pk_aviso_producto PRIMARY KEY (id_aviso),
  CONSTRAINT ck_producto_precio CHECK (precio >= 0),
  CONSTRAINT fk_producto_aviso FOREIGN KEY (id_aviso) REFERENCES aviso (id_aviso) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE tipo_inmueble (
  id_tipo_inmueble  TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre            VARCHAR(30) NOT NULL,
  CONSTRAINT pk_tipo_inmueble PRIMARY KEY (id_tipo_inmueble),
  CONSTRAINT uq_tipo_inmueble_nombre UNIQUE (nombre)
);

CREATE TABLE aviso_inmobiliario (
  id_aviso          INT UNSIGNED NOT NULL,
  id_tipo_inmueble  TINYINT UNSIGNED NOT NULL,
  ubicacion         VARCHAR(150) NOT NULL,
  superficie_m2     DECIMAL(10,2) NOT NULL,
  precio            DECIMAL(14,2) NOT NULL,
  CONSTRAINT pk_aviso_inmobiliario PRIMARY KEY (id_aviso),
  CONSTRAINT ck_inmo_superficie CHECK (superficie_m2 > 0),
  CONSTRAINT ck_inmo_precio CHECK (precio >= 0),
  CONSTRAINT fk_inmo_aviso FOREIGN KEY (id_aviso) REFERENCES aviso (id_aviso) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_inmo_tipo FOREIGN KEY (id_tipo_inmueble) REFERENCES tipo_inmueble (id_tipo_inmueble) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE auditoria_backup (
  id_backup        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo             ENUM('COMPLETO','INCREMENTAL','PRUEBA_RESTAURACION') NOT NULL,
  estado           ENUM('EN_CURSO','OK','ERROR') NOT NULL DEFAULT 'EN_CURSO',
  fecha_inicio     DATETIME NOT NULL,
  fecha_fin        DATETIME NULL,
  archivo          VARCHAR(255) NULL,
  tamano_bytes     BIGINT UNSIGNED NULL,
  checksum_sha256  CHAR(64) NULL,
  usuario_bd       VARCHAR(100) NOT NULL,
  observaciones    VARCHAR(255) NULL,
  CONSTRAINT pk_auditoria_backup PRIMARY KEY (id_backup),
  CONSTRAINT ck_backup_fechas CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio),
  INDEX idx_backup_fecha (fecha_inicio)
);

CREATE VIEW v_avisos_publicos AS
SELECT a.id_aviso, a.titulo, a.fecha_creacion, a.resaltado,
       au.nombre AS autor, c.nombre AS categoria, a.id_autor,
       COALESCE(l.empresa, ti.nombre)                                     AS referencia,
       COALESCE(l.descripcion_cargo, p.descripcion_producto, i.ubicacion) AS detalle,
       COALESCE(l.sueldo_mensual, p.precio, i.precio)                     AS monto
FROM aviso a
JOIN autor au      ON au.id_autor = a.id_autor
JOIN categoria c   ON c.id_categoria = a.id_categoria
LEFT JOIN aviso_laboral l       ON l.id_aviso = a.id_aviso
LEFT JOIN aviso_producto p      ON p.id_aviso = a.id_aviso
LEFT JOIN aviso_inmobiliario i  ON i.id_aviso = a.id_aviso
LEFT JOIN tipo_inmueble ti      ON ti.id_tipo_inmueble = i.id_tipo_inmueble;


DELIMITER $$

CREATE FUNCTION fn_autor_tiene_permiso(p_id_autor INT UNSIGNED, p_codigo VARCHAR(50))
RETURNS TINYINT(1)
DETERMINISTIC READS SQL DATA
BEGIN
  RETURN EXISTS (
    SELECT 1
    FROM autor a
    JOIN rol_permiso rp ON rp.id_rol = a.id_rol
    JOIN permiso p      ON p.id_permiso = rp.id_permiso
    WHERE a.id_autor = p_id_autor AND a.activo = 1 AND p.codigo = p_codigo
  );
END$$

CREATE PROCEDURE sp_datos_login(IN p_email VARCHAR(120))
BEGIN
  SELECT a.id_autor, a.nombre, a.password_hash, r.nombre AS rol
  FROM autor a JOIN rol r ON r.id_rol = a.id_rol
  WHERE a.email = p_email AND a.activo = 1;
END$$

CREATE PROCEDURE sp_permisos_autor(IN p_id_autor INT UNSIGNED)
BEGIN
  SELECT p.codigo
  FROM autor a
  JOIN rol_permiso rp ON rp.id_rol = a.id_rol
  JOIN permiso p      ON p.id_permiso = rp.id_permiso
  WHERE a.id_autor = p_id_autor AND a.activo = 1;
END$$

CREATE PROCEDURE sp_publicar_laboral(
  IN p_id_autor INT UNSIGNED, IN p_titulo VARCHAR(150), IN p_resaltado TINYINT(1),
  IN p_empresa VARCHAR(100), IN p_cargo TEXT, IN p_sueldo DECIMAL(10,2),
  OUT p_id_aviso INT UNSIGNED)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;
  IF NOT fn_autor_tiene_permiso(p_id_autor, 'listing.create') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sin permiso: listing.create';
  END IF;
  IF p_resaltado = 1 AND NOT fn_autor_tiene_permiso(p_id_autor, 'listing.highlight') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sin permiso: listing.highlight';
  END IF;
  START TRANSACTION;
    INSERT INTO aviso (titulo, resaltado, id_autor, id_categoria)
      SELECT p_titulo, p_resaltado, p_id_autor, id_categoria FROM categoria WHERE nombre = 'Laboral';
    SET p_id_aviso = LAST_INSERT_ID();
    INSERT INTO aviso_laboral (id_aviso, empresa, descripcion_cargo, sueldo_mensual)
      VALUES (p_id_aviso, p_empresa, p_cargo, p_sueldo);
  COMMIT;
END$$

CREATE PROCEDURE sp_publicar_producto(
  IN p_id_autor INT UNSIGNED, IN p_titulo VARCHAR(150), IN p_resaltado TINYINT(1),
  IN p_descripcion TEXT, IN p_precio DECIMAL(12,2),
  OUT p_id_aviso INT UNSIGNED)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;
  IF NOT fn_autor_tiene_permiso(p_id_autor, 'listing.create') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sin permiso: listing.create';
  END IF;
  IF p_resaltado = 1 AND NOT fn_autor_tiene_permiso(p_id_autor, 'listing.highlight') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sin permiso: listing.highlight';
  END IF;
  START TRANSACTION;
    INSERT INTO aviso (titulo, resaltado, id_autor, id_categoria)
      SELECT p_titulo, p_resaltado, p_id_autor, id_categoria FROM categoria WHERE nombre = 'Producto';
    SET p_id_aviso = LAST_INSERT_ID();
    INSERT INTO aviso_producto (id_aviso, descripcion_producto, precio)
      VALUES (p_id_aviso, p_descripcion, p_precio);
  COMMIT;
END$$

CREATE PROCEDURE sp_publicar_inmobiliario(
  IN p_id_autor INT UNSIGNED, IN p_titulo VARCHAR(150), IN p_resaltado TINYINT(1),
  IN p_id_tipo TINYINT UNSIGNED, IN p_ubicacion VARCHAR(150),
  IN p_superficie DECIMAL(10,2), IN p_precio DECIMAL(14,2),
  OUT p_id_aviso INT UNSIGNED)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;
  IF NOT fn_autor_tiene_permiso(p_id_autor, 'listing.create') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sin permiso: listing.create';
  END IF;
  IF p_resaltado = 1 AND NOT fn_autor_tiene_permiso(p_id_autor, 'listing.highlight') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sin permiso: listing.highlight';
  END IF;
  START TRANSACTION;
    INSERT INTO aviso (titulo, resaltado, id_autor, id_categoria)
      SELECT p_titulo, p_resaltado, p_id_autor, id_categoria FROM categoria WHERE nombre = 'Inmobiliario';
    SET p_id_aviso = LAST_INSERT_ID();
    INSERT INTO aviso_inmobiliario (id_aviso, id_tipo_inmueble, ubicacion, superficie_m2, precio)
      VALUES (p_id_aviso, p_id_tipo, p_ubicacion, p_superficie, p_precio);
  COMMIT;
END$$

CREATE PROCEDURE sp_eliminar_aviso(IN p_id_aviso INT UNSIGNED, IN p_id_autor INT UNSIGNED)
BEGIN
  DECLARE v_dueno INT UNSIGNED;
  SELECT id_autor INTO v_dueno FROM aviso WHERE id_aviso = p_id_aviso;
  IF v_dueno IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El aviso no existe';
  END IF;
  IF fn_autor_tiene_permiso(p_id_autor, 'listing.delete_any')
     OR (v_dueno = p_id_autor AND fn_autor_tiene_permiso(p_id_autor, 'listing.delete_own')) THEN
    DELETE FROM aviso WHERE id_aviso = p_id_aviso;
  ELSE
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sin permiso para eliminar este aviso';
  END IF;
END$$

CREATE PROCEDURE sp_backup_iniciar(IN p_tipo VARCHAR(30), OUT p_id_backup BIGINT UNSIGNED)
BEGIN
  INSERT INTO auditoria_backup (tipo, estado, fecha_inicio, usuario_bd)
  VALUES (p_tipo, 'EN_CURSO', NOW(), CURRENT_USER());
  SET p_id_backup = LAST_INSERT_ID();
END$$

CREATE PROCEDURE sp_backup_finalizar(
  IN p_id_backup BIGINT UNSIGNED, IN p_estado VARCHAR(10), IN p_archivo VARCHAR(255),
  IN p_tamano BIGINT UNSIGNED, IN p_checksum CHAR(64), IN p_obs VARCHAR(255))
BEGIN
  UPDATE auditoria_backup
     SET estado = p_estado, fecha_fin = NOW(), archivo = p_archivo,
         tamano_bytes = p_tamano, checksum_sha256 = p_checksum, observaciones = p_obs
   WHERE id_backup = p_id_backup AND estado = 'EN_CURSO';
END$$

CREATE TRIGGER trg_laboral_categoria BEFORE INSERT ON aviso_laboral FOR EACH ROW
BEGIN
  IF (SELECT c.nombre FROM aviso a JOIN categoria c ON c.id_categoria = a.id_categoria
      WHERE a.id_aviso = NEW.id_aviso) <> 'Laboral' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El aviso no pertenece a la categoría Laboral';
  END IF;
END$$

CREATE TRIGGER trg_producto_categoria BEFORE INSERT ON aviso_producto FOR EACH ROW
BEGIN
  IF (SELECT c.nombre FROM aviso a JOIN categoria c ON c.id_categoria = a.id_categoria
      WHERE a.id_aviso = NEW.id_aviso) <> 'Producto' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El aviso no pertenece a la categoría Producto';
  END IF;
END$$

CREATE TRIGGER trg_inmo_categoria BEFORE INSERT ON aviso_inmobiliario FOR EACH ROW
BEGIN
  IF (SELECT c.nombre FROM aviso a JOIN categoria c ON c.id_categoria = a.id_categoria
      WHERE a.id_aviso = NEW.id_aviso) <> 'Inmobiliario' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El aviso no pertenece a la categoría Inmobiliario';
  END IF;
END$$

CREATE TRIGGER trg_aviso_categoria_inmutable BEFORE UPDATE ON aviso FOR EACH ROW
BEGIN
  IF NEW.id_categoria <> OLD.id_categoria THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede cambiar la categoría de un aviso';
  END IF;
END$$

CREATE PROCEDURE sp_listar_autores()
BEGIN
  SELECT a.id_autor AS id, a.nombre AS name, a.email, r.nombre AS role, a.activo
  FROM autor a JOIN rol r ON r.id_rol = a.id_rol
  ORDER BY a.id_autor DESC;
END$$

CREATE PROCEDURE sp_crear_autor(IN p_nombre VARCHAR(100), IN p_email VARCHAR(120), IN p_password_hash VARCHAR(255), IN p_rol_nombre VARCHAR(30), OUT p_id_autor INT UNSIGNED)
BEGIN
  DECLARE v_id_rol TINYINT UNSIGNED;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  SELECT id_rol INTO v_id_rol FROM rol WHERE nombre = p_rol_nombre;
  START TRANSACTION;
    INSERT INTO autor (nombre, email, password_hash, id_rol, activo) VALUES (p_nombre, p_email, p_password_hash, v_id_rol, 1);
    SET p_id_autor = LAST_INSERT_ID();
  COMMIT;
END$$

CREATE PROCEDURE sp_eliminar_autor(IN p_id_autor INT UNSIGNED)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  START TRANSACTION;
    DELETE FROM autor WHERE id_autor = p_id_autor;
  COMMIT;
END$$

DELIMITER ;
