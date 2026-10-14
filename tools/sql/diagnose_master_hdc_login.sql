-- Ejecutar en la base master_usuarios configurada en el .env del hosting.
-- Solo lectura: no modifica cuentas, contrasenas ni permisos.
SELECT DATABASE() AS base_actual;

SELECT
    u.ID AS usuario_id,
    u.USER AS usuario,
    u.ACTIVO AS usuario_activo,
    s.ID AS sistema_id,
    s.SLUG AS sistema,
    s.ACTIVO AS sistema_activo,
    us.ID AS asignacion_id,
    us.ACTIVO AS asignacion_activa,
    CASE
        WHEN u.ACTIVO <> 1 THEN 'USUARIO_INACTIVO'
        WHEN s.ID IS NULL THEN 'SISTEMA_MASTER_HDC_NO_EXISTE'
        WHEN s.ACTIVO <> 1 THEN 'SISTEMA_INACTIVO'
        WHEN us.ID IS NULL THEN 'SIN_ASIGNACION'
        WHEN us.ACTIVO <> 1 THEN 'ASIGNACION_INACTIVA'
        ELSE 'ACCESO_CONFIGURADO'
    END AS diagnostico
FROM `user` u
LEFT JOIN sistemas_hdc s ON s.SLUG = 'master_hdc'
LEFT JOIN usuario_sistemas us ON us.USER_ID = u.ID AND us.SISTEMA_ID = s.ID
WHERE LOWER(TRIM(u.USER)) = 'cagurtoc';
