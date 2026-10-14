<?php

namespace App\Controllers;

use App\Libraries\Totp;
use App\Models\MasterUsuarioModel;
use App\Models\PasswordResetModel;

class AuthController extends BaseController
{

    private const MASTER_ADMIN_USERS = ['cagurtoc', 'jsepulvedar'];
    private string $accessDiagnostic = 'L02-R2';
    private const LOGIN_ERROR = 'No fue posible iniciar sesion. Verifica tus credenciales o espera unos minutos.';
    private const MAX_LOGIN_ATTEMPTS = 3;
    private const LOCKOUT_SECONDS = 180;

    public function login()
    {
        if ($this->session->get('mfa_user_id')) {
            return redirect()->to(site_url('mfa'));
        }

        if ($this->session->get('usuario_id')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    public function attempt()
    {
        $usuario = $this->cleanIdentifier((string) $this->request->getPost('usuario'));
        $password = (string) $this->request->getPost('password');

        // Validate the same POST values used for authentication. Hosting settings
        // can exclude POST from $_REQUEST or allow other input to override it.
        if (! $this->validateData(['usuario' => $usuario, 'password' => $password], [
            'usuario' => 'required|max_length[20]|regex_match[/^[0-9A-Za-zÁÉÍÓÚÜÑáéíóúüñ@._+\\-]+$/u]',
            'password' => 'required|min_length[8]|max_length[10]',
        ])) {
            return redirect()->to(site_url('login'))->withInput()->with(
                'error',
                'Revisa el formato del usuario y la contrasena (entre 8 y 10 caracteres). [L01]'
            );
        }

        $model = new MasterUsuarioModel();
        $user = $this->findActiveUserForLogin($model, $usuario);

        if ($this->isLoginLocked($user)) {
            return redirect()->to(site_url('login'))->withInput()->with('error', $this->lockedMessage($user));
        }

        if (! $user || ! password_verify($password, (string) $user['PASSWORD'])) {
            if ($user) {
                $loginState = $this->registerFailedLogin($model, $user);

                return redirect()->to(site_url('login'))->withInput()
                    ->with('error', ! empty($loginState['locked'])
                        ? $this->lockoutMessage(self::LOCKOUT_SECONDS)
                        : $this->failedAttemptMessage((int) $loginState['remaining']));
            }

            return redirect()->to(site_url('login'))->withInput()
                ->with('error', $this->failedAttemptMessage(self::MAX_LOGIN_ATTEMPTS));
        }

        if (! $this->isAuthorizedForMasterHdc($user)) {
            return redirect()->to(site_url('login'))->withInput()->with(
                'error',
                'Credenciales correctas, pero no fue posible autorizar el acceso a Master HDC. Contacta al administrador. [' . $this->accessDiagnostic . ']'
            );
        }

        $model->update((int) $user['ID'], [
            'FAILED_LOGIN_ATTEMPTS' => 0,
            'LOCKED_UNTIL' => null,
            'LAST_LOGIN_AT' => date('Y-m-d H:i:s'),
        ]);

        if ($this->mfaIsEnabledFor($user)) {
            $mfaStart = $this->beginMfaChallenge($user);
            if ($mfaStart !== null) {
                return $mfaStart;
            }
        }

        return $this->completeLogin($user);
    }

    public function mfa()
    {
        $pending = $this->pendingMfaUser();
        if (! $pending) {
            return redirect()->to(site_url('login'))->with('error', 'Debes iniciar sesion.');
        }

        $settings = $this->mfaSettings();
        $emailAvailable = ! empty($settings['email_enabled']) && filter_var((string) ($pending['CORREO'] ?? ''), FILTER_VALIDATE_EMAIL);
        $totpEnabled = ! empty($settings['authenticator_enabled']);
        $showSetup = $totpEnabled && empty($pending['MFA_TOTP_ENABLED']);
        $setupUri = null;

        if ($showSetup) {
            $setupSecret = $this->session->get('mfa_setup_secret') ?: Totp::generateSecret();
            $this->session->set('mfa_setup_secret', $setupSecret);
            $account = (string) ($pending['CORREO'] ?: (($pending['USER'] ?? '') ?: (($pending['RUT'] ?? '') . '-' . ($pending['DV'] ?? ''))));
            $setupUri = Totp::provisioningUri((string) ($settings['issuer'] ?? 'Master HDC'), $account, $setupSecret);
        }

        return view('auth/mfa', [
            'emailAvailable' => $emailAvailable,
            'totpAvailable' => $totpEnabled,
            'showSetup' => $showSetup,
            'setupUri' => $setupUri,
            'emailCodeSent' => (string) $this->session->get('mfa_email_hash') !== '',
        ]);
    }

    public function sendMfaEmailCode()
    {
        $pending = $this->pendingMfaUser();
        $settings = $this->mfaSettings();
        $correo = trim((string) ($pending['CORREO'] ?? ''));

        if (! $pending || empty($settings['email_enabled']) || ! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return redirect()->to(site_url('mfa'))->with('error', 'No hay un correo valido para enviar el codigo.');
        }

        $code = (string) random_int(100000, 999999);
        $token = (string) $this->session->get('mfa_token');
        $this->session->set([
            'mfa_email_hash' => hash('sha256', $code . '|' . $token),
            'mfa_email_expires' => time() + 600,
            'mfa_preferred_method' => 'email',
        ]);

        $this->sendMfaEmail($correo, $pending, $code);

        $message = 'Codigo MFA enviado al correo registrado.';
        if (ENVIRONMENT === 'development') {
            $message .= ' Codigo DEV: ' . $code;
        }

        return redirect()->to(site_url('mfa'))->with('success', $message);
    }

    public function verifyMfa()
    {
        $pending = $this->pendingMfaUser();
        if (! $pending) {
            return redirect()->to(site_url('login'))->with('error', 'La verificacion MFA expiro.');
        }

        $settings = $this->mfaSettings();
        $method = in_array((string) $this->request->getPost('method'), ['email', 'totp'], true)
            ? (string) $this->request->getPost('method')
            : '';
        $code = substr(preg_replace('/\D+/', '', (string) $this->request->getPost('code')) ?? '', 0, 8);
        $ok = false;

        if ($method === 'email' && ! empty($settings['email_enabled'])) {
            $hash = (string) $this->session->get('mfa_email_hash');
            $expires = (int) $this->session->get('mfa_email_expires');
            $token = (string) $this->session->get('mfa_token');
            $ok = $hash !== '' && $expires >= time() && hash_equals($hash, hash('sha256', $code . '|' . $token));
        }

        if ($method === 'totp' && ! empty($settings['authenticator_enabled'])) {
            $secret = ! empty($pending['MFA_TOTP_ENABLED']) ? (string) $pending['MFA_TOTP_SECRET'] : (string) $this->session->get('mfa_setup_secret');
            $ok = $secret !== '' && Totp::verify($secret, $code);
            if ($ok && empty($pending['MFA_TOTP_ENABLED'])) {
                (new MasterUsuarioModel())->update((int) $pending['ID'], [
                    'MFA_TOTP_SECRET' => $secret,
                    'MFA_TOTP_ENABLED' => 1,
                ]);
                $pending['MFA_TOTP_SECRET'] = $secret;
                $pending['MFA_TOTP_ENABLED'] = 1;
            }
        }

        if (! $ok) {
            return redirect()->to(site_url('mfa'))->with('error', 'Codigo MFA invalido o expirado.');
        }

        $this->clearMfaChallenge();

        return $this->completeLogin($pending);
    }

    public function logout()
    {
        $this->session->destroy();

        return redirect()->to(site_url('login'));
    }

    public function forgotPassword()
    {
        if ($this->session->get('usuario_id')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/forgot_password');
    }

    public function sendResetLink()
    {
        $correo = $this->cleanEmail((string) $this->request->getPost('correo'));

        if (! $this->validate(['correo' => 'required|valid_email'])) {
            return redirect()->to(site_url('forgot-password'))->withInput()
                ->with('error', 'Ingresa un correo electrónico válido.');
        }

        $user = (new MasterUsuarioModel())->where('CORREO', $correo)->where('ACTIVO', 1)->first();
        if (! $user) {
            return redirect()->to(site_url('login'))
                ->with('success', 'Si existe una cuenta activa, enviaremos instrucciones de recuperacion al correo indicado.');
        }

        $resetModel = new PasswordResetModel();
        $token = $resetModel->crearToken($correo, 3600);
        $enlace = site_url('reset-password/' . $token);
        $enviado = $this->sendPasswordResetEmail($correo, $user, $enlace);

        if (! $enviado) {
            if (ENVIRONMENT === 'development') {
                log_message('info', 'Password reset Master HDC DEV para {c}: {e}', ['c' => $correo, 'e' => $enlace]);

                return redirect()->to(site_url('login'))
                    ->with('success', 'No se pudo enviar el correo. [DEV] Enlace: ' . $enlace);
            }

            $resetModel->marcarActivosUsadosPorEmail($correo);

            return redirect()->to(site_url('forgot-password'))->withInput()
                ->with('error', 'No fue posible enviar el correo de recuperacion. Revisa la configuracion SMTP o intenta nuevamente.');
        }

        log_message('info', 'Password reset Master HDC enviado a {c}', ['c' => $correo]);

        $msg = 'Te enviamos un enlace para restablecer tu contraseña. Revisa tu correo (válido por 1 hora).';
        return redirect()->to(site_url('login'))->with('success', $msg);
    }

    public function resetPassword(string $token)
    {
        $reset = (new PasswordResetModel())->tokenValido($token);

        return view('auth/reset_password', [
            'token' => $reset ? $token : null,
            'invalido' => $reset === null,
        ]);
    }

    public function updatePassword(string $token)
    {
        $model = new PasswordResetModel();
        $reset = $model->tokenValido($token);
        if (! $reset) {
            return redirect()->to(site_url('login'))->with('error', 'El enlace de recuperación es inválido o expiró.');
        }

        if (! $this->validate([
            'password' => 'required|min_length[8]|max_length[10]',
            'password_confirm' => 'required|matches[password]',
        ])) {
            return redirect()->to(site_url('reset-password/' . $token))
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $usuarioModel = new MasterUsuarioModel();
        $user = $usuarioModel->where('CORREO', $reset['email'])->first();
        if (! $user) {
            return redirect()->to(site_url('login'))->with('error', 'La cuenta asociada ya no existe.');
        }

        $usuarioModel->update((int) $user['ID'], [
            'PASSWORD' => password_hash((string) $this->request->getPost('password'), PASSWORD_BCRYPT),
            'PASSWORD_CHANGED_AT' => date('Y-m-d H:i:s'),
        ]);
        $model->marcarUsado((int) $reset['id']);

        return redirect()->to(site_url('login'))->with('success', 'Tu contraseña fue actualizada. Ya puedes iniciar sesión.');
    }

    private function completeLogin(array $user)
    {
        $nombre = $this->nombreUsuario($user);

        $this->session->regenerate(true);
        $this->session->set([
            'usuario_id' => $user['ID'],
            'nombre' => $nombre,
            'email' => $user['CORREO'],
            'hospital_id' => null,
            'last_activity_at' => time(),
        ]);

        return redirect()->to('/dashboard');
    }

    private function findActiveUserForLogin(MasterUsuarioModel $model, string $identifier): ?array
    {
        $email = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? mb_strtolower($identifier) : '';
        $rut = $this->splitRut($identifier);

        $builder = $model->where('ACTIVO', 1)
            ->groupStart()
            ->where('USER', $identifier);

        if ($email !== '') {
            $builder->orWhere('CORREO', $email);
        }

        if ($rut !== null) {
            $builder->orGroupStart()
                ->where('RUT', $rut['rut'])
                ->where('DV', $rut['dv'])
                ->groupEnd();
        }

        return $builder->groupEnd()->first();
    }

    private function cleanIdentifier(string $value): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/\s+/', '', $value) ?? '';

        return mb_substr($value, 0, 20);
    }

    private function cleanEmail(string $value): string
    {
        return mb_strtolower(mb_substr(trim(strip_tags($value)), 0, 160));
    }

    private function splitRut(string $identifier): ?array
    {
        $rut = strtoupper(preg_replace('/[^0-9K]/i', '', $identifier) ?? '');
        if (strlen($rut) < 2) {
            return null;
        }

        return [
            'rut' => substr($rut, 0, -1),
            'dv' => substr($rut, -1),
        ];
    }

    private function isLoginLocked(?array $user): bool
    {
        if (! $user || empty($user['LOCKED_UNTIL'])) {
            return false;
        }

        return strtotime((string) $user['LOCKED_UNTIL']) > time();
    }

    private function registerFailedLogin(MasterUsuarioModel $model, array $user): array
    {
        $attempts = ((int) ($user['FAILED_LOGIN_ATTEMPTS'] ?? 0)) + 1;
        $data = ['FAILED_LOGIN_ATTEMPTS' => $attempts];
        $locked = false;

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $locked = true;
            $data['FAILED_LOGIN_ATTEMPTS'] = 0;
            $data['LOCKED_UNTIL'] = date('Y-m-d H:i:s', time() + self::LOCKOUT_SECONDS);
        }

        $model->update((int) $user['ID'], $data);

        return [
            'locked' => $locked,
            'remaining' => $locked ? 0 : max(0, self::MAX_LOGIN_ATTEMPTS - $attempts),
        ];
    }

    private function failedAttemptMessage(int $remaining): string
    {
        $attemptLabel = $remaining === 1 ? 'intento' : 'intentos';

        return self::LOGIN_ERROR . ' Te quedan ' . $remaining . ' ' . $attemptLabel . ' antes del bloqueo temporal.';
    }

    private function lockedMessage(?array $user): string
    {
        $seconds = self::LOCKOUT_SECONDS;
        if ($user && ! empty($user['LOCKED_UNTIL'])) {
            $seconds = max(1, strtotime((string) $user['LOCKED_UNTIL']) - time());
        }

        return $this->lockoutMessage($seconds);
    }

    private function lockoutMessage(int $seconds): string
    {
        return 'Cuenta bloqueada temporalmente por ' . $this->formatLockoutTime($seconds)
            . '. Puedes usar recuperar contrasena o esperar 3 minutos para volver a ingresar tu contrasena.';
    }

    private function formatLockoutTime(int $seconds): string
    {
        $seconds = max(1, $seconds);
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes <= 0) {
            return $remainingSeconds . ' segundos';
        }

        if ($remainingSeconds === 0) {
            return $minutes . ($minutes === 1 ? ' minuto' : ' minutos');
        }

        return $minutes . ($minutes === 1 ? ' minuto' : ' minutos') . ' y ' . $remainingSeconds . ' segundos';
    }

    private function nombreUsuario(array $user): string
    {
        $nombres = preg_split('/\s+/', trim((string) ($user['NOMBRE'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
        $partes = [
            $nombres[0] ?? '',
            trim((string) ($user['APELLIDO_PATERNO'] ?? '')),
            trim((string) ($user['APELLIDO_MATERNO'] ?? '')),
        ];

        return trim(implode(' ', array_filter($partes))) ?: 'usuario';
    }

    private function mfaIsEnabledFor(array $user): bool
    {
        $settings = $this->mfaSettings();

        return ! empty($settings['enabled'])
            && (int) ($user['MFA_REQUIRED'] ?? 1) === 1
            && (! empty($settings['email_enabled']) || ! empty($settings['authenticator_enabled']));
    }

    private function mfaSettings(): array
    {
        try {
            $db = db_connect('master_usuarios');
            if ($db->tableExists('sistema_mfa_settings')) {
                $row = $db->table('sistema_mfa_settings m')
                    ->select('m.MFA_ENABLED AS enabled, m.EMAIL_ENABLED AS email_enabled, m.AUTHENTICATOR_ENABLED AS authenticator_enabled, m.ISSUER AS issuer')
                    ->join('sistemas_hdc s', 's.ID = m.SISTEMA_ID')
                    ->where('s.SLUG', \App\Libraries\MasterSystem::slug())
                    ->get()
                    ->getRowArray();

                if ($row) {
                    return $row;
                }
            }

            if (! $db->tableExists('security_mfa_settings')) {
                return ['enabled' => 1, 'email_enabled' => 1, 'authenticator_enabled' => 1, 'issuer' => 'Master HDC'];
            }

            $row = $db->table('security_mfa_settings')->where('id', 1)->get()->getRowArray();

            return $row ?: ['enabled' => 1, 'email_enabled' => 1, 'authenticator_enabled' => 1, 'issuer' => 'Master HDC'];
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo leer configuracion MFA: {m}', ['m' => $e->getMessage()]);

            return ['enabled' => 1, 'email_enabled' => 1, 'authenticator_enabled' => 1, 'issuer' => 'Master HDC'];
        }
    }

    private function beginMfaChallenge(array $user)
    {
        $settings = $this->mfaSettings();
        $emailAvailable = ! empty($settings['email_enabled']) && filter_var((string) ($user['CORREO'] ?? ''), FILTER_VALIDATE_EMAIL);
        $totpAvailable = ! empty($settings['authenticator_enabled']);

        if (! $emailAvailable && ! $totpAvailable) {
            return redirect()->to(site_url('login'))->with('error', 'MFA esta activo, pero la cuenta no tiene un metodo disponible.');
        }

        $this->session->regenerate(true);
        $this->session->set([
            'mfa_user_id' => (int) $user['ID'],
            'mfa_token' => bin2hex(random_bytes(16)),
            'mfa_expires' => time() + 600,
            'mfa_email_hash' => null,
            'mfa_email_expires' => null,
            'mfa_setup_secret' => null,
            'mfa_preferred_method' => null,
        ]);

        return redirect()->to(site_url('mfa'));
    }

    private function pendingMfaUser(): ?array
    {
        $userId = (int) $this->session->get('mfa_user_id');
        if (! $userId || (int) $this->session->get('mfa_expires') < time()) {
            $this->clearMfaChallenge();

            return null;
        }

        $user = (new MasterUsuarioModel())->find($userId);
        if (! $user || empty($user['ACTIVO']) || ! $this->isAuthorizedForMasterHdc($user)) {
            $this->clearMfaChallenge();

            return null;
        }

        return $user;
    }

    private function isAuthorizedForMasterHdc(array $user): bool
    {
        $username = mb_strtolower(trim((string) ($user['USER'] ?? '')));
        if (! in_array($username, self::MASTER_ADMIN_USERS, true)) {
            $this->accessDiagnostic = 'L02-R2-CUENTA';
            log_message('error', 'Master HDC acceso denegado: cuenta fuera de administradores permitidos. User ID: {id}', [
                'id' => (int) ($user['ID'] ?? 0),
            ]);
            return false;
        }

        $context = ['revision' => 'L02-R2', 'user_id' => (int) ($user['ID'] ?? 0), 'archivo' => __FILE__];
        try {
            $db = db_connect('master_usuarios');
            $context['database'] = $db->getDatabase();
            $context['slug'] = \App\Libraries\MasterSystem::slug();
            $row = $db->table('usuario_sistemas us')
                ->select('us.ID')
                ->join('sistemas_hdc s', 's.ID = us.SISTEMA_ID')
                ->where('us.USER_ID', (int) ($user['ID'] ?? 0))
                ->where('us.ACTIVO', 1)
                ->where('s.ACTIVO', 1)
                ->where('s.SLUG', $context['slug'])
                ->get()
                ->getRowArray();

            if ($row === null) {
                $this->accessDiagnostic = 'L02-R2-ASIGNACION';
                log_message('error', 'Master HDC L02-R2 sin asignacion activa: {context}', [
                    'context' => json_encode($context, JSON_UNESCAPED_SLASHES),
                ]);
            }

            return $row !== null;
        } catch (\Throwable $e) {
            $this->accessDiagnostic = 'L02-R2-CONSULTA';
            log_message('error', 'Master HDC L02-R2 fallo de consulta: {context}; error: {m}', [
                'context' => json_encode($context, JSON_UNESCAPED_SLASHES), 'm' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function clearMfaChallenge(): void
    {
        $this->session->remove([
            'mfa_user_id',
            'mfa_token',
            'mfa_expires',
            'mfa_email_hash',
            'mfa_email_expires',
            'mfa_setup_secret',
            'mfa_preferred_method',
        ]);
    }

    private function sendMfaEmail(string $correo, array $user, string $code): bool
    {
        try {
            $nombre = $this->nombreUsuario($user);
            $mensaje = '<p>Hola ' . esc($nombre) . ',</p>'
                . '<p>Tu codigo de verificacion MFA para Master HDC es:</p>'
                . '<p style="font-size:24px;font-weight:700;letter-spacing:4px">' . esc($code) . '</p>'
                . '<p>El codigo vence en 10 minutos. Si no intentaste iniciar sesion, informa este evento.</p>';

            $subject = 'Codigo MFA - Master HDC';
            $email = service('email');
            $email->setTo($correo);
            $email->setSubject($subject);
            $email->setMailType('html');
            $email->setMessage($mensaje);

            $sent = $email->send(false);
            if (! $sent) {
                $this->logEmailConfigSnapshot($email, 'MFA');
                log_message('error', 'No se pudo enviar correo MFA a {correo}. Debug: {debug}', [
                    'correo' => $correo,
                    'debug' => trim(strip_tags($email->printDebugger(['headers', 'subject']))),
                ]);

                $sent = $this->sendWithPhpMailFallback($correo, $subject, $mensaje, 'MFA');
            }

            return $sent;
        } catch (\Throwable $e) {
            log_message('error', 'Error enviando correo MFA: {msg}', ['msg' => $e->getMessage()]);

            return false;
        }
    }

    private function sendPasswordResetEmail(string $correo, array $user, string $enlace): bool
    {
        try {
            $nombre = $this->nombreUsuario($user);
            $subject = 'Recuperacion de contrasena - Master HDC';
            $mensaje = '<p>Hola ' . esc($nombre) . ',</p>'
                . '<p>Recibimos una solicitud para restablecer tu contraseña en Master HDC.</p>'
                . '<p><a href="' . esc($enlace) . '" style="background:#1D6FE8;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;display:inline-block;">Crear nueva contraseña</a></p>'
                . '<p>O copia este enlace en tu navegador:<br>' . esc($enlace) . '</p>'
                . '<p>El enlace vence en 1 hora. Si no solicitaste esto, ignora este correo.</p>';

            $email = service('email');
            $email->setTo($correo);
            $email->setSubject('Recuperación de contraseña - Master HDC');
            $email->setMailType('html');
            $email->setMessage($mensaje);

            $sent = $email->send(false);
            if (! $sent) {
                $this->logEmailConfigSnapshot($email, 'recuperacion');
                log_message('error', 'No se pudo enviar correo de recuperacion a {correo}. Debug: {debug}', [
                    'correo' => $correo,
                    'debug' => trim(strip_tags($email->printDebugger(['headers', 'subject']))),
                ]);

                $sent = $this->sendWithPhpMailFallback($correo, $subject, $mensaje, 'recuperacion');
            }

            return $sent;
        } catch (\Throwable $e) {
            log_message('error', 'Error enviando correo de recuperación Master HDC: {msg}', ['msg' => $e->getMessage()]);

            return false;
        }
    }

    private function logEmailConfigSnapshot(object $email, string $contexto): void
    {
        $password = (string) ($email->SMTPPass ?? '');

        log_message('error', 'Config correo {ctx}: protocol={protocol}, host={host}, port={port}, crypto={crypto}, user={user}, from={from}, pass_len={passLen}', [
            'ctx' => $contexto,
            'protocol' => (string) ($email->protocol ?? ''),
            'host' => (string) ($email->SMTPHost ?? ''),
            'port' => (string) ($email->SMTPPort ?? ''),
            'crypto' => (string) ($email->SMTPCrypto ?? ''),
            'user' => (string) ($email->SMTPUser ?? ''),
            'from' => (string) ($email->fromEmail ?? ''),
            'passLen' => strlen($password),
        ]);
    }

    private function sendWithPhpMailFallback(string $correo, string $subject, string $mensaje, string $contexto): bool
    {
        try {
            $baseConfig = get_object_vars(config('Email'));
            $fallbackConfig = array_merge($baseConfig, [
                'protocol' => 'mail',
                'SMTPHost' => '',
                'SMTPUser' => '',
                'SMTPPass' => '',
                'SMTPPort' => 25,
                'SMTPCrypto' => '',
            ]);

            $email = service('email', $fallbackConfig, false);
            $email->setTo($correo);
            $email->setSubject($subject);
            $email->setMailType('html');
            $email->setMessage($mensaje);

            $sent = $email->send(false);
            if ($sent) {
                log_message('info', 'Correo {ctx} enviado a {correo} usando fallback mail().', [
                    'ctx' => $contexto,
                    'correo' => $correo,
                ]);

                return true;
            }

            log_message('error', 'Fallback mail() fallo para correo {ctx} a {correo}. Debug: {debug}', [
                'ctx' => $contexto,
                'correo' => $correo,
                'debug' => trim(strip_tags($email->printDebugger(['headers', 'subject']))),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Fallback mail() lanzo error para correo {ctx}: {msg}', [
                'ctx' => $contexto,
                'msg' => $e->getMessage(),
            ]);
        }

        return false;
    }
}
