# Sistema Master HDC

Sistema web para la administracion centralizada de usuarios, sistemas institucionales, accesos, autenticacion multifactor, configuracion corporativa y seguridad base del Hospital de Cauquenes.

La plataforma esta construida sobre CodeIgniter 4 y esta orientada a funcionar como punto maestro de identidad para los sistemas HDC, permitiendo mantener usuarios activos, controlar permisos por sistema, configurar MFA, gestionar datos institucionales y entregar trazabilidad administrativa para el acceso a aplicaciones internas.

## Tabla de Contenidos

- [Objetivo del sistema](#objetivo-del-sistema)
- [Como funciona el flujo principal](#como-funciona-el-flujo-principal)
- [Modulos funcionales](#modulos-funcionales)
- [Roles, perfiles y alcance](#roles-perfiles-y-alcance)
- [Arquitectura tecnica](#arquitectura-tecnica)
- [Modelo de datos principal](#modelo-de-datos-principal)
- [Autenticacion y seguridad de acceso](#autenticacion-y-seguridad-de-acceso)
- [Instalacion local en WAMP](#instalacion-local-en-wamp)
- [Configuracion del entorno](#configuracion-del-entorno)
- [Migraciones y carga inicial](#migraciones-y-carga-inicial)
- [Seguridad aplicada](#seguridad-aplicada)
- [Rutas principales](#rutas-principales)
- [Mantenimiento y operacion](#mantenimiento-y-operacion)

## Objetivo del Sistema

El Sistema Master HDC permite administrar de forma centralizada la identidad digital y los accesos de los usuarios que utilizan los sistemas institucionales del Hospital de Cauquenes.

El sistema esta disenado para:

- Registrar y mantener usuarios maestros de la institucion.
- Gestionar credenciales, datos personales, cargo, unidad, servicio y centro de costo.
- Administrar sistemas HDC disponibles para asignacion de acceso.
- Relacionar usuarios con uno o mas sistemas institucionales.
- Activar, desactivar y auditar accesos por usuario.
- Configurar MFA global y MFA por sistema.
- Permitir verificacion MFA por correo o aplicacion Authenticator.
- Recuperar contrasenas mediante enlaces temporales.
- Bloquear temporalmente cuentas ante intentos fallidos.
- Mantener configuracion institucional, logos, colores y datos de contacto.
- Entregar un dashboard administrativo con indicadores de usuarios, sistemas y MFA.

## Como Funciona el Flujo Principal

El sistema se organiza alrededor de la administracion de identidad: ingreso seguro, mantencion de usuarios, asignacion de sistemas, configuracion de MFA y seguimiento administrativo.

### 1. Inicio de Sesion

Un usuario autorizado ingresa con su identificador, correo o RUT, junto con su contrasena.

El flujo contempla:

- Validacion de usuario activo.
- Revision de bloqueo temporal por intentos fallidos.
- Limpieza y normalizacion de identificadores.
- Inicio de desafio MFA cuando el sistema o la cuenta lo requiere.
- Regeneracion segura de sesion al autenticar correctamente.

### 2. Verificacion MFA

Cuando MFA esta habilitado, el sistema solicita un segundo factor.

Los metodos disponibles son:

- Codigo enviado por correo electronico.
- Codigo TOTP mediante aplicacion Authenticator.

El sistema permite configurar el secreto TOTP en el primer ingreso y reiniciar MFA de un usuario cuando corresponde.

### 3. Administracion de Usuarios

El administrador mantiene la nomina maestra de usuarios institucionales.

El modulo permite:

- Crear usuarios.
- Editar datos personales y laborales.
- Asociar centro de costo y servicio.
- Activar o desactivar usuarios.
- Cambiar credenciales.
- Exigir MFA por cuenta.
- Reiniciar configuracion MFA.
- Asignar accesos a sistemas HDC.
- Buscar y filtrar usuarios por texto o sistema.

### 4. Administracion de Sistemas

El administrador registra las aplicaciones institucionales que pueden recibir accesos.

El modulo permite:

- Crear sistemas.
- Editar nombre, slug, descripcion y URL.
- Activar o desactivar sistemas.
- Configurar MFA por sistema.
- Definir metodos permitidos de segundo factor.
- Mantener emisores MFA para cada aplicacion.

### 5. Configuracion Institucional

El sistema permite administrar datos visuales y corporativos de la institucion activa.

Se puede configurar:

- Codigo institucional.
- Nombre y razon social.
- RUT.
- Direccion.
- Telefono.
- Correo.
- Sitio web.
- Color principal.
- Logo principal.
- Logo secundario.
- Favicon.
- Estado activo y seleccion institucional.

### 6. Dashboard Administrativo

El dashboard consolida informacion operativa de identidad y accesos:

- Total de usuarios.
- Usuarios activos e inactivos.
- Sistemas activos.
- Accesos activos.
- Usuarios con MFA pendiente.
- Listado de accesos por sistema.
- Usuarios que requieren completar Authenticator.

## Modulos Funcionales

### Panel de Control

Vista principal para monitoreo administrativo.

Incluye:

- Indicadores de usuarios y sistemas.
- Resumen de accesos activos.
- Alertas de MFA pendiente.
- Detalle de usuarios con segundo factor requerido.
- Accesos rapidos a usuarios, sistemas y configuracion.

### Usuarios Master

Modulo central para mantener la identidad de usuarios HDC.

Permite:

- Crear y editar usuarios.
- Mantener RUT, nombre, apellidos, correo, usuario y cargo.
- Asociar departamento, unidad, centro de costo y servicio.
- Asignar sistemas autorizados.
- Exigir MFA individual.
- Activar o desactivar cuentas.
- Reiniciar configuracion MFA.
- Filtrar por nombre, usuario, correo, RUT o sistema.

### Sistemas HDC

Modulo para administrar el catalogo de sistemas institucionales.

Registra:

- Nombre.
- Slug.
- URL.
- Descripcion.
- Estado activo.
- Configuracion MFA asociada.

Estados habituales:

| Estado | Uso |
| --- | --- |
| `activo` | Sistema disponible para asignar accesos. |
| `inactivo` | Sistema oculto o suspendido administrativamente. |

### MFA por Sistema

Modulo de configuracion de segundo factor asociado a cada sistema.

Permite controlar:

- Si MFA esta habilitado para el sistema.
- Si se permite codigo por correo.
- Si se permite Authenticator/TOTP.
- Nombre del emisor visible en la aplicacion MFA.

### Configuracion

Modulo para administrar la identidad institucional del portal.

Permite:

- Crear o editar instituciones.
- Seleccionar institucion activa.
- Cargar logos y favicon.
- Definir color principal.
- Mantener datos de contacto.
- Configurar MFA global del portal Master HDC.

### Recuperacion de Contrasena

Flujo publico para recuperar acceso mediante correo registrado.

Incluye:

- Solicitud de enlace temporal.
- Validacion de token vigente.
- Actualizacion de contrasena.
- Marcado de token como utilizado.

## Roles, Perfiles y Alcance

El sistema esta pensado para usuarios administradores o encargados de gestion de accesos.

El alcance funcional se divide en:

| Perfil operativo | Alcance |
| --- | --- |
| Administrador Master | Mantiene usuarios, sistemas, accesos, MFA y configuracion institucional. |
| Usuario autenticado | Ingresa al sistema, completa MFA y accede a las vistas autorizadas. |
| Cuenta con MFA requerido | Debe completar segundo factor antes de entrar al dashboard. |

El control de acceso se basa en sesion autenticada y en la informacion registrada en la base maestra de usuarios y sistemas.

## Arquitectura Tecnica

El proyecto utiliza CodeIgniter 4 con estructura MVC.

Componentes principales:

- `app/Controllers`: controladores de autenticacion, dashboard, usuarios, sistemas y configuracion.
- `app/Models`: modelos para usuarios, sistemas, relacion usuario-sistema y recuperacion de contrasena.
- `app/Views`: vistas de login, MFA, dashboard, formularios y listados.
- `app/Config/Routes.php`: rutas publicas y rutas protegidas por filtro `auth`.
- `app/Database/Migrations`: estructura base para identidad, MFA y bloqueo de login.
- `app/Database/Seeds`: carga inicial del sistema Master HDC.
- `tools/`: scripts de apoyo para empaquetado de produccion y correccion de dumps.

Dependencias relevantes:

- PHP 8.3 o superior.
- CodeIgniter 4.
- MySQL/MariaDB.
- phpoffice/phpspreadsheet.
- laminas/laminas-escaper.
- PHPUnit para pruebas.

## Modelo de Datos Principal

Tablas principales utilizadas por el sistema:

| Tabla | Proposito |
| --- | --- |
| `user` | Usuarios maestros de la institucion. |
| `sistemas_hdc` | Catalogo de sistemas institucionales. |
| `usuario_sistemas` | Relacion entre usuarios y sistemas autorizados. |
| `sistema_mfa_settings` | Configuracion MFA por sistema. |
| `security_mfa_settings` | Configuracion MFA global del portal. |
| `security_institutions` | Datos institucionales, logos, colores y contacto. |
| `password_resets` | Tokens de recuperacion de contrasena. |
| `centro_costo` | Centros de costo para clasificacion administrativa. |
| `servicios` | Servicios asociados a usuarios y centros de costo. |

## Autenticacion y Seguridad de Acceso

El sistema implementa controles de acceso para proteger la administracion de identidad.

Incluye:

- Login con usuario, correo o RUT.
- Hash seguro de contrasenas.
- Regeneracion de sesion al autenticar.
- Filtro `auth` para rutas privadas.
- Bloqueo temporal por intentos fallidos.
- MFA por correo y Authenticator.
- Tokens temporales para recuperacion de contrasena.
- Limpieza de entradas de texto, slugs, correos y numeros.
- Validacion de archivos de branding por extension, MIME y tamano.

## Instalacion Local en WAMP

1. Clonar el repositorio:

```bash
git clone https://github.com/cagurto31/SIS_MASTER_HDC.git
cd SIS_MASTER_HDC
```

2. Instalar dependencias:

```bash
composer install
```

3. Copiar archivo de entorno:

```bash
copy env .env
```

4. Configurar base URL local:

```ini
app.baseURL = 'http://localhost/SIS_MASTER_HDC/public/'
app.indexPage = ''
```

5. Configurar base de datos:

```ini
database.default.hostname = localhost
database.default.database = cho66450_Master_usuarios
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi

database.master_usuarios.hostname = localhost
database.master_usuarios.database = cho66450_Master_usuarios
database.master_usuarios.username = root
database.master_usuarios.password =
database.master_usuarios.DBDriver = MySQLi
```

6. Ejecutar migraciones y carga inicial:

```bash
php spark migrate
php spark db:seed InitialSeeder
```

7. Abrir en navegador:

```text
http://localhost/SIS_MASTER_HDC/public/
```

## Configuracion del Entorno

Variables principales:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost/SIS_MASTER_HDC/public/'
app.indexPage = ''

database.default.database = cho66450_Master_usuarios
database.master_usuarios.database = cho66450_Master_usuarios
```

En produccion se recomienda:

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://hospitalcauquenes.cl/webapp/master_hdc/'
app.indexPage = ''
database.default.DBDebug = false
database.master_usuarios.DBDebug = false
```

## Migraciones y Carga Inicial

Migraciones incluidas:

| Migracion | Funcion |
| --- | --- |
| `CreateMasterHdcIdentitySchema` | Crea estructura base de sistemas, usuario-sistema, instituciones y recuperacion. |
| `CreateSistemaMfaSettings` | Agrega configuracion MFA por sistema. |
| `AddLoginLockoutToUsers` | Agrega campos para bloqueo por intentos fallidos. |
| `AlterUserLoginLength` | Ajusta largo de identificador de usuario. |

Comandos utiles:

```bash
php spark migrate
php spark db:seed InitialSeeder
php spark migrate:status
```

## Seguridad Aplicada

Buenas practicas del proyecto:

- No subir `.env` ni archivos con credenciales.
- Mantener `vendor/` fuera del repositorio.
- Mantener `writable/` con permisos controlados.
- Usar PHP 8.3 o superior.
- Desactivar `DBDebug` en produccion.
- Revisar logs en `writable/logs`.
- Validar que la carpeta publica apunte solo a `public/`.
- Mantener MFA activo para cuentas administrativas.

## Rutas Principales

| Ruta | Descripcion |
| --- | --- |
| `/` | Redirecciona a login o dashboard segun sesion. |
| `/login` | Inicio de sesion. |
| `/logout` | Cierre de sesion. |
| `/forgot-password` | Solicitud de recuperacion de contrasena. |
| `/reset-password/{token}` | Cambio de contrasena con token valido. |
| `/mfa` | Verificacion de segundo factor. |
| `/dashboard` | Panel administrativo. |
| `/master-usuarios` | Listado y busqueda de usuarios. |
| `/master-usuarios/crear` | Crear usuario. |
| `/master-usuarios/editar/{id}` | Editar usuario. |
| `/sistemas` | Listado de sistemas HDC. |
| `/sistemas/crear` | Crear sistema. |
| `/sistemas/editar/{id}` | Editar sistema. |
| `/configuracion` | Configuracion institucional y MFA global. |
| `/branding/{tipo}` | Entrega de logo, logo secundario o favicon activo. |

## Mantenimiento y Operacion

Tareas recomendadas:

- Revisar periodicamente usuarios inactivos.
- Validar usuarios con MFA pendiente.
- Mantener actualizada la URL de cada sistema HDC.
- Verificar que los sistemas inactivos no se asignen a nuevos usuarios.
- Respaldar la base `master_usuarios` antes de cambios masivos.
- Revisar logs de errores despues de actualizaciones.
- Ejecutar migraciones en ambientes controlados antes de produccion.
- Regenerar paquetes de produccion con los scripts de `tools/` cuando cambie el codigo.

Para despliegue en hosting, revisar `PRODUCCION.md`.
