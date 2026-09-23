# Paso a produccion Master HDC

## Carpetas en hosting

- Privada: `/home/cho66450/SIS_MASTER_HDC/`
- Publica: `/home/cho66450/public_html/webapp/master_hdc/`

La carpeta privada debe contener `app`, `system`, `vendor`, `writable`, `spark`, `composer.json`, `composer.lock`, `.env` y `.htaccess`.

La carpeta publica debe contener solo el contenido de `public/`: `index.php`, `.htaccess`, `robots.txt` y futuros assets publicos.

## Preparar ZIPs desde Windows

```powershell
powershell -ExecutionPolicy Bypass -File tools\Prepare-ProductionPackage.ps1 -IncludeEnv
```

Se generan:

- `build/production/SIS_MASTER_HDC_private.zip`
- `build/production/master_hdc_public.zip`

Sube el contenido del ZIP privado a `/home/cho66450/SIS_MASTER_HDC/` y el contenido del ZIP publico a `/home/cho66450/public_html/webapp/master_hdc/`.

## .env en produccion

En `/home/cho66450/SIS_MASTER_HDC/.env` debe quedar:

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://hospitalcauquenes.cl/webapp/master_hdc/'
app.indexPage = ''
database.default.database = cho66450_master_usuarios
database.master_usuarios.database = cho66450_master_usuarios
database.default.DBDebug = false
database.master_usuarios.DBDebug = false
```

Confirma que el nombre de la BD coincida exactamente con phpMyAdmin. En Linux importa la mayuscula/minuscula.

## PHP requerido

Selecciona PHP 8.3 o superior en cPanel/MultiPHP. El `vendor` instalado requiere PHP `>= 8.3.0`.

## Error phpMyAdmin `utf8mb4_0900_ai_ci`

Ese collation pertenece a MySQL 8. Muchos hostings con MariaDB/MySQL 5.7 no lo reconocen.

Antes de importar, corrige el dump:

```powershell
powershell -ExecutionPolicy Bypass -File tools\Fix-MySqlDumpForHosting.ps1 -InputSql C:\ruta\cho66450_master_usuarios.sql
```

Importa el archivo generado `*.hosting.sql`.

Si ya importaste parcialmente, lo mas limpio es vaciar o recrear la BD y volver a importar el dump corregido.

## Verificacion rapida

1. Abre `https://hospitalcauquenes.cl/webapp/master_hdc/`.
2. Si aparece 404, revisa que `mod_rewrite` este activo y que `RewriteBase /webapp/master_hdc/` exista en la `.htaccess` publica.
3. Si aparece error 500, revisa `writable/logs` y permisos de escritura en `writable`.
4. Ejecuta migraciones si faltan tablas:

```bash
cd /home/cho66450/SIS_MASTER_HDC
php spark migrate
php spark db:seed InitialSeeder
```
