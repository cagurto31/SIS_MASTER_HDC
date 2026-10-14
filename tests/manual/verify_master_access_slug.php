<?php
// Base SQLite en memoria; no consulta ni modifica cuentas reales.
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
$config = config('Database');
$config->master_usuarios = [
    'DBDriver'=>'SQLite3', 'database'=>':memory:', 'DBPrefix'=>'', 'DBDebug'=>true,
];
$db = db_connect('master_usuarios');
$db->query('CREATE TABLE sistemas_hdc (ID INTEGER PRIMARY KEY, SLUG TEXT, ACTIVO INTEGER)');
$db->query('CREATE TABLE usuario_sistemas (ID INTEGER PRIMARY KEY, USER_ID INTEGER, SISTEMA_ID INTEGER, ACTIVO INTEGER)');
$db->query("INSERT INTO sistemas_hdc VALUES (17,'sis_master_hdc',1),(31,'master_hdc',1)");
$db->query('INSERT INTO usuario_sistemas VALUES (1,42,17,1)');
$controller = new \App\Controllers\AuthController();
$method = new ReflectionMethod($controller, 'isAuthorizedForMasterHdc');
$user = ['ID'=>42,'USER'=>'cagurtoc'];
$setSlug = static function (string $slug): void {
    $_ENV['security.systemSlug']=$slug;
    $_SERVER['security.systemSlug']=$slug;
    putenv('security.systemSlug='.$slug);
};
$check = static function (bool $ok, string $message): void {
    if (! $ok) throw new RuntimeException($message);
};
$setSlug('master_hdc');
$check(! $method->invoke($controller,$user), 'No debe autorizar otro sistema');
$setSlug('sis_master_hdc');
$check($method->invoke($controller,$user), 'Debe aceptar asignacion del SLUG configurado');
$check(! $method->invoke($controller,['ID'=>42,'USER'=>'otro_usuario']), 'Debe conservar restriccion de administradores');
$db->query('UPDATE usuario_sistemas SET ACTIVO=0');
$check(! $method->invoke($controller,$user), 'Asignacion inactiva no puede acceder');
$db->query('UPDATE usuario_sistemas SET ACTIVO=1');
$db->query('UPDATE sistemas_hdc SET ACTIVO=0 WHERE ID=17');
$check(! $method->invoke($controller,$user), 'Sistema inactivo no puede acceder');
$db->query('UPDATE sistemas_hdc SET ACTIVO=1 WHERE ID=17');
$db->query('CREATE TABLE sistema_mfa_settings (SISTEMA_ID INTEGER, MFA_ENABLED INTEGER, EMAIL_ENABLED INTEGER, AUTHENTICATOR_ENABLED INTEGER, ISSUER TEXT)');
$db->query("INSERT INTO sistema_mfa_settings VALUES (17,1,1,0,'MFA produccion')");
$mfa = (new ReflectionMethod($controller,'mfaSettings'))->invoke($controller);
$check($mfa['issuer']==='MFA produccion' && (int)$mfa['enabled']===1, 'MFA debe resolver el mismo SLUG');
echo "OK: SLUG configurado, aislamiento entre sistemas, administradores, acceso desactivado y MFA.\n";
