/*
  MASTER_HDC - Modulo de perfiles y roles

  Script opcional para produccion.
  La aplicacion tambien crea estas tablas automaticamente al entrar a Configuracion.
*/

USE `cho66450_master_usuarios`;

CREATE TABLE IF NOT EXISTS security_roles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    icon VARCHAR(60) NOT NULL DEFAULT 'bi-person-fill',
    color VARCHAR(20) NOT NULL DEFAULT '#1D6FE8',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS security_profiles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    role_code VARCHAR(50) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_security_profiles_name (name),
    KEY idx_security_profiles_role (role_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS security_profile_permissions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    permission_key VARCHAR(120) NOT NULL,
    UNIQUE KEY uq_security_profile_permission (profile_id, permission_key),
    KEY idx_security_profile_permissions_profile (profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO security_roles (code, name, description, icon, color, active, created_at, updated_at)
SELECT 'administrador', 'Administrador', 'Acceso total al sistema, configuracion, perfiles y seguridad.', 'bi-shield-fill-check', '#dc2626', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM security_roles WHERE code = 'administrador');

INSERT INTO security_roles (code, name, description, icon, color, active, created_at, updated_at)
SELECT 'operador', 'Operador', 'Gestion operativa de usuarios, sistemas y accesos.', 'bi-person-gear', '#2563eb', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM security_roles WHERE code = 'operador');

INSERT INTO security_roles (code, name, description, icon, color, active, created_at, updated_at)
SELECT 'directivo', 'Directivo', 'Consulta institucional y supervision de informacion.', 'bi-briefcase-fill', '#7c3aed', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM security_roles WHERE code = 'directivo');

INSERT INTO security_roles (code, name, description, icon, color, active, created_at, updated_at)
SELECT 'conductor', 'Conductor', 'Rol homologado desde Movilizacion para sistemas integrados.', 'bi-truck-front-fill', '#059669', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM security_roles WHERE code = 'conductor');

INSERT INTO security_roles (code, name, description, icon, color, active, created_at, updated_at)
SELECT 'solicitante', 'Solicitante', 'Acceso basico de consulta para usuarios solicitantes.', 'bi-person-lines-fill', '#d97706', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM security_roles WHERE code = 'solicitante');

INSERT INTO security_roles (code, name, description, icon, color, active, created_at, updated_at)
SELECT 'enfermera', 'Enfermera', 'Rol clinico homologado desde Movilizacion para validacion centralizada.', 'bi-heart-pulse-fill', '#0f766e', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM security_roles WHERE code = 'enfermera');

INSERT INTO security_roles (code, name, description, icon, color, active, created_at, updated_at)
SELECT 'tens', 'TENS', 'Rol TENS homologado desde Movilizacion para validacion centralizada.', 'bi-prescription2', '#0891b2', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM security_roles WHERE code = 'tens');

INSERT INTO security_profiles (name, description, role_code, active, created_at, updated_at)
SELECT 'Perfil Administrador', 'Acceso completo de gestion, configuracion y mantenimiento.', 'administrador', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM security_profiles WHERE name = 'Perfil Administrador');

INSERT INTO security_profiles (name, description, role_code, active, created_at, updated_at)
SELECT 'Perfil Operador', 'Gestion de usuarios, sistemas y accesos sin administracion de roles.', 'operador', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM security_profiles WHERE name = 'Perfil Operador');

INSERT INTO security_profiles (name, description, role_code, active, created_at, updated_at)
SELECT 'Perfil Solicitante', 'Perfil base de consulta para sistemas que validan contra MASTER_HDC.', 'solicitante', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM security_profiles WHERE name = 'Perfil Solicitante');

INSERT IGNORE INTO security_profile_permissions (profile_id, permission_key)
SELECT p.id, x.permission_key
FROM security_profiles p
JOIN (
    SELECT 'dashboard' permission_key UNION ALL
    SELECT 'master-usuarios' UNION ALL
    SELECT 'sistemas' UNION ALL
    SELECT 'configuracion' UNION ALL
    SELECT 'configuracion/perfiles' UNION ALL
    SELECT 'configuracion/mfa' UNION ALL
    SELECT 'usuarios/crear' UNION ALL
    SELECT 'usuarios/editar' UNION ALL
    SELECT 'usuarios/desactivar' UNION ALL
    SELECT 'usuarios/reset-mfa' UNION ALL
    SELECT 'sistemas/crear' UNION ALL
    SELECT 'sistemas/editar' UNION ALL
    SELECT 'sistemas/mfa' UNION ALL
    SELECT 'branding'
) x
WHERE p.name = 'Perfil Administrador';

INSERT IGNORE INTO security_profile_permissions (profile_id, permission_key)
SELECT p.id, x.permission_key
FROM security_profiles p
JOIN (
    SELECT 'dashboard' permission_key UNION ALL
    SELECT 'master-usuarios' UNION ALL
    SELECT 'sistemas' UNION ALL
    SELECT 'usuarios/crear' UNION ALL
    SELECT 'usuarios/editar' UNION ALL
    SELECT 'usuarios/desactivar'
) x
WHERE p.name = 'Perfil Operador';

INSERT IGNORE INTO security_profile_permissions (profile_id, permission_key)
SELECT p.id, 'dashboard'
FROM security_profiles p
WHERE p.name = 'Perfil Solicitante';

SELECT r.id, r.code, r.name, r.icon, r.color, r.active
FROM security_roles r
ORDER BY r.id;

SELECT p.id, p.name, p.role_code, COUNT(pp.id) AS permisos, p.active
FROM security_profiles p
LEFT JOIN security_profile_permissions pp ON pp.profile_id = p.id
GROUP BY p.id, p.name, p.role_code, p.active
ORDER BY p.active DESC, p.name;
