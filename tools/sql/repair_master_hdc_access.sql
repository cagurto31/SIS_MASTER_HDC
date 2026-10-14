-- Ejecutar en la base master_usuarios del hosting.
-- Registra Master HDC y habilita exclusivamente el acceso de cagurtoc.
START TRANSACTION;

INSERT INTO sistemas_hdc
    (NOMBRE, SLUG, URL, ACTIVO, CREATED_AT, UPDATED_AT)
VALUES
    ('Master HDC', 'master_hdc',
     'https://hospitalcauquenes.cl/webapp/master_hdc/', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE ACTIVO = 1, UPDATED_AT = NOW();

INSERT INTO usuario_sistemas
    (USER_ID, SISTEMA_ID, ACTIVO, CREATED_AT, UPDATED_AT)
SELECT u.ID, s.ID, 1, NOW(), NOW()
FROM `user` u
JOIN sistemas_hdc s ON s.SLUG = 'master_hdc'
WHERE u.USER = 'cagurtoc' AND u.ACTIVO = 1
ON DUPLICATE KEY UPDATE ACTIVO = 1, UPDATED_AT = NOW();

COMMIT;

SELECT u.USER AS usuario, u.ACTIVO AS usuario_activo,
       s.SLUG AS sistema, s.ACTIVO AS sistema_activo,
       us.ACTIVO AS asignacion_activa
FROM `user` u
JOIN usuario_sistemas us ON us.USER_ID = u.ID
JOIN sistemas_hdc s ON s.ID = us.SISTEMA_ID
WHERE u.USER = 'cagurtoc' AND s.SLUG = 'master_hdc';
