SET NAMES utf8mb4;
USE avisos_clasificados;


SELECT id_aviso, titulo, categoria, autor, fecha_creacion, resaltado
FROM v_avisos_publicos
ORDER BY resaltado DESC, fecha_creacion DESC, id_aviso DESC;

SELECT a.id_aviso, a.titulo, l.empresa, COALESCE(CAST(l.sueldo_mensual AS CHAR),'A convenir') AS sueldo
FROM aviso a JOIN aviso_laboral l ON l.id_aviso = a.id_aviso
ORDER BY a.fecha_creacion DESC;

SELECT a.titulo, ti.nombre AS tipo, i.ubicacion, i.superficie_m2, i.precio,
       ROUND(i.precio / i.superficie_m2, 2) AS precio_m2
FROM aviso a
JOIN aviso_inmobiliario i ON i.id_aviso = a.id_aviso
JOIN tipo_inmueble ti ON ti.id_tipo_inmueble = i.id_tipo_inmueble
ORDER BY precio_m2;

SELECT c.nombre AS categoria, COUNT(a.id_aviso) AS total, SUM(a.resaltado) AS resaltados
FROM categoria c LEFT JOIN aviso a ON a.id_categoria = c.id_categoria
GROUP BY c.id_categoria, c.nombre;

SELECT au.nombre, au.email, COUNT(*) AS avisos
FROM autor au JOIN aviso a ON a.id_autor = au.id_autor
GROUP BY au.id_autor, au.nombre, au.email
HAVING COUNT(*) >= 2;

SELECT au.nombre FROM autor au
WHERE NOT EXISTS (SELECT 1 FROM aviso a WHERE a.id_autor = au.id_autor);

SELECT a.titulo, p.precio
FROM aviso a JOIN aviso_producto p ON p.id_aviso = a.id_aviso
WHERE p.precio > (SELECT AVG(precio) FROM aviso_producto);

SELECT au.nombre, a.titulo, a.fecha_creacion,
       ROW_NUMBER() OVER (PARTITION BY au.id_autor ORDER BY a.fecha_creacion DESC) AS posicion
FROM aviso a JOIN autor au ON au.id_autor = a.id_autor;

SELECT r.nombre AS rol, GROUP_CONCAT(p.codigo ORDER BY p.codigo SEPARATOR ', ') AS permisos
FROM rol r
LEFT JOIN rol_permiso rp ON rp.id_rol = r.id_rol
LEFT JOIN permiso p ON p.id_permiso = rp.id_permiso
GROUP BY r.id_rol, r.nombre;

SELECT id_aviso, titulo, fecha_creacion
FROM aviso WHERE fecha_creacion >= NOW() - INTERVAL 7 DAY;

SELECT * FROM auditoria_backup WHERE estado = 'OK' ORDER BY fecha_inicio DESC LIMIT 1;

EXPLAIN SELECT id_aviso FROM aviso ORDER BY resaltado DESC, fecha_creacion DESC LIMIT 20;
