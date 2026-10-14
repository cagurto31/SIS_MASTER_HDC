<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class ConfigurationController extends BaseController
{
    private $db;
    private const ASSETS = ['menu_logo', 'login_logo', 'favicon', 'document_logo'];

    public function __construct()
    {
        $this->db = db_connect('master_usuarios');
    }

    public function index(): string
    {
        $this->ensureTables();

        return view('configuration/index', [
            'titulo' => 'Configuracion',
            'usuario' => $this->usuario(),
            'institutions' => $this->db->table('security_institutions')
                ->orderBy('selected', 'DESC')
                ->orderBy('name')
                ->get()
                ->getResultArray(),
            'mfa' => $this->mfaSettings(),
            'roles' => $this->db->table('security_roles')
                ->orderBy('id')
                ->get()
                ->getResultArray(),
            'profiles' => $this->getProfilesWithPermissions(),
            'permissionsCatalog' => $this->availablePermissions(),
            'flatPermissions' => $this->flatPermissions(),
        ]);
    }

    public function save(?int $id = null): RedirectResponse
    {
        $this->ensureTables();

        $data = [];
        foreach (['code', 'name', 'legal_name', 'rut', 'address', 'commune', 'region', 'phone', 'email', 'website', 'primary_color'] as $field) {
            $data[$field] = $this->cleanText((string) $this->request->getPost($field), 220) ?: null;
        }

        $data['code'] = strtoupper($this->cleanSlug((string) $data['code'], 30));
        $data['rut'] = $this->cleanText((string) $data['rut'], 20) ?: null;
        $data['phone'] = $this->cleanText((string) $data['phone'], 30) ?: null;
        $data['email'] = mb_strtolower($this->cleanText((string) $data['email'], 160)) ?: null;
        $data['website'] = $this->cleanText((string) $data['website'], 200) ?: null;
        $data['primary_color'] = $this->cleanText((string) $data['primary_color'], 7) ?: '#1D6FE8';
        $data['active'] = $this->request->getPost('active') ? 1 : 0;
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (! $data['code'] || ! $data['name'] || ! preg_match('/^[A-Z0-9_-]{2,30}$/', $data['code'])) {
            return redirect()->back()->withInput()->with('error', 'Codigo y nombre son obligatorios. El codigo solo admite letras, numeros, guion y guion bajo.');
        }

        if ($data['email'] && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'El correo institucional no es valido.');
        }

        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $data['primary_color'])) {
            $data['primary_color'] = '#1D6FE8';
        }

        $duplicate = $this->db->table('security_institutions')->where('code', $data['code']);
        if ($id) {
            $duplicate->where('id !=', $id);
        }

        if ($duplicate->countAllResults()) {
            return redirect()->back()->withInput()->with('error', 'El codigo institucional ya existe.');
        }

        $current = $id ? $this->db->table('security_institutions')->where('id', $id)->get()->getRowArray() : null;
        if ($current && ! $data['active'] && (int) $current['selected'] === 1) {
            return redirect()->back()->withInput()->with('error', 'No puede desactivar la empresa actualmente seleccionada. Seleccione primero otra empresa.');
        }

        foreach (self::ASSETS as $field) {
            $file = $this->request->getFile($field);
            if ($file && $file->isValid() && ! $file->hasMoved()) {
                $stored = $this->storeImage($file, $field);
                if ($stored === null) {
                    return redirect()->back()->withInput()->with('error', 'Archivo no valido en ' . str_replace('_', ' ', $field) . '. Use PNG, JPG, WEBP o ICO, maximo 3 MB.');
                }
                $data[$field] = $stored;
            }
        }

        if ($id) {
            $this->db->table('security_institutions')->where('id', $id)->update($data);
        } else {
            $data['created_at'] = $data['updated_at'];
            $data['selected'] = $this->db->table('security_institutions')->countAllResults() === 0 ? 1 : 0;
            $this->db->table('security_institutions')->insert($data);
        }

        return redirect()->to(site_url('configuracion'))->with('success', 'Informacion institucional guardada.');
    }

    public function select(int $id): RedirectResponse
    {
        $this->ensureTables();

        $row = $this->db->table('security_institutions')->where(['id' => $id, 'active' => 1])->get()->getRowArray();
        if (! $row) {
            return redirect()->back()->with('error', 'La empresa no existe o esta inactiva.');
        }

        $this->db->transStart();
        $this->db->table('security_institutions')->update(['selected' => 0]);
        $this->db->table('security_institutions')->where('id', $id)->update([
            'selected' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->transComplete();

        return redirect()->to(site_url('configuracion'))->with('success', 'Empresa activa: ' . $row['name'] . '.');
    }

    public function saveMfa(): RedirectResponse
    {
        $this->ensureTables();

        $enabled = $this->request->getPost('enabled') ? 1 : 0;
        $email = $this->request->getPost('email_enabled') ? 1 : 0;
        $authenticator = $this->request->getPost('authenticator_enabled') ? 1 : 0;

        if ($enabled && ! $email && ! $authenticator) {
            return redirect()->back()->with('error', 'Debe habilitar al menos un metodo MFA.');
        }

        $issuer = $this->cleanText((string) $this->request->getPost('issuer'), 120) ?: 'Master HDC';
        $data = [
            'id' => 1,
            'enabled' => $enabled,
            'email_enabled' => $email,
            'authenticator_enabled' => $authenticator,
            'issuer' => mb_substr($issuer, 0, 120),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $exists = $this->db->table('security_mfa_settings')->where('id', 1)->countAllResults() > 0;
        if ($exists) {
            $this->db->table('security_mfa_settings')->where('id', 1)->update($data);
        } else {
            $this->db->table('security_mfa_settings')->insert($data);
        }

        return redirect()->to(site_url('configuracion'))->with('success', 'Configuracion MFA guardada.');
    }

    public function saveProfile(?int $id = null): RedirectResponse
    {
        $this->ensureTables();

        $name = $this->cleanText((string) $this->request->getPost('name'), 100);
        $description = $this->cleanText((string) $this->request->getPost('description'), 255) ?: null;
        $roleCode = $this->cleanSlug((string) $this->request->getPost('role_code'), 50) ?: null;
        $permissions = $this->normalizePermissions((array) ($this->request->getPost('permissions') ?? []));

        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'El nombre del perfil es obligatorio.');
        }

        if ($roleCode && ! $this->db->table('security_roles')->where('code', $roleCode)->countAllResults()) {
            return redirect()->back()->withInput()->with('error', 'El rol seleccionado no existe.');
        }

        if ($permissions === []) {
            return redirect()->back()->withInput()->with('error', 'Seleccione al menos un permiso para el perfil.');
        }

        $duplicate = $this->db->table('security_profiles')->where('name', $name);
        if ($id) {
            $duplicate->where('id !=', $id);
        }

        if ($duplicate->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('error', 'Ya existe un perfil con ese nombre.');
        }

        $data = [
            'name' => $name,
            'description' => $description,
            'role_code' => $roleCode,
            'permissions' => $permissions,
            'active' => $this->request->getPost('active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id) {
            unset($data['permissions']);
            $this->db->table('security_profiles')->where('id', $id)->update($data);
        } else {
            unset($data['permissions']);
            $data['created_at'] = $data['updated_at'];
            $this->db->table('security_profiles')->insert($data);
            $id = (int) $this->db->insertID();
        }

        $this->syncProfilePermissions((int) $id, $permissions);

        return redirect()->to(site_url('configuracion'))->with('success', 'Perfil guardado.');
    }

    public function toggleProfile(int $id): RedirectResponse
    {
        $this->ensureTables();

        $row = $this->db->table('security_profiles')->where('id', $id)->get()->getRowArray();
        if (! $row) {
            return redirect()->to(site_url('configuracion'))->with('error', 'Perfil no encontrado.');
        }

        $active = (int) ($row['active'] ?? 0) === 1 ? 0 : 1;
        $this->db->table('security_profiles')->where('id', $id)->update([
            'active' => $active,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('configuracion'))->with('success', $active ? 'Perfil activado.' : 'Perfil desactivado.');
    }

    public function deleteProfile(int $id): RedirectResponse
    {
        $this->ensureTables();

        $row = $this->db->table('security_profiles')->where('id', $id)->get()->getRowArray();
        if (! $row) {
            return redirect()->to(site_url('configuracion'))->with('error', 'Perfil no encontrado.');
        }

        if ((new \App\Models\SecurityProfileModel())->assignedUserCount($id) > 0) {
            return redirect()->to(site_url('configuracion'))->with('error', 'Este perfil tiene usuarios asignados. Reasignelos antes de eliminarlo.');
        }

        $this->db->transStart();
        $this->db->table('security_profile_permissions')->where('profile_id', $id)->delete();
        $this->db->table('security_profiles')->where('id', $id)->delete();
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to(site_url('configuracion'))->with('error', 'No se pudo eliminar el perfil.');
        }

        return redirect()->to(site_url('configuracion'))->with('success', 'Perfil eliminado.');
    }

    public function saveRole(?int $id = null): RedirectResponse
    {
        $this->ensureTables();

        $isNew = $id === null;
        $code = $this->cleanSlug((string) $this->request->getPost('code'), 50);
        $name = $this->cleanText((string) $this->request->getPost('name'), 100);
        $description = $this->cleanText((string) $this->request->getPost('description'), 255) ?: null;
        $icon = $this->cleanText((string) $this->request->getPost('icon'), 60) ?: 'bi-person-fill';
        $color = $this->cleanText((string) $this->request->getPost('color'), 20) ?: '#1D6FE8';

        if ($isNew && ($code === '' || ! preg_match('/^[a-z0-9_]{2,50}$/', $code))) {
            return redirect()->back()->withInput()->with('error', 'La clave del rol es obligatoria y solo admite letras, numeros y guion bajo.');
        }

        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'El nombre del rol es obligatorio.');
        }

        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#1D6FE8';
        }

        if ($isNew) {
            if ($this->db->table('security_roles')->where('code', $code)->countAllResults() > 0) {
                return redirect()->back()->withInput()->with('error', 'Ya existe un rol con esa clave.');
            }
        } else {
            $role = $this->db->table('security_roles')->where('id', $id)->get()->getRowArray();
            if (! $role) {
                return redirect()->to(site_url('configuracion'))->with('error', 'Rol no encontrado.');
            }
            $code = (string) $role['code'];
        }

        $data = [
            'name' => $name,
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
            'active' => $this->request->getPost('active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($isNew) {
            $data['code'] = $code;
            $data['created_at'] = $data['updated_at'];
            $this->db->table('security_roles')->insert($data);
        } else {
            $this->db->table('security_roles')->where('id', $id)->update($data);
        }

        return redirect()->to(site_url('configuracion'))->with('success', 'Rol guardado.');
    }

    public function toggleRole(int $id): RedirectResponse
    {
        $this->ensureTables();

        $row = $this->db->table('security_roles')->where('id', $id)->get()->getRowArray();
        if (! $row) {
            return redirect()->to(site_url('configuracion'))->with('error', 'Rol no encontrado.');
        }

        $active = (int) ($row['active'] ?? 0) === 1 ? 0 : 1;
        $this->db->table('security_roles')->where('id', $id)->update([
            'active' => $active,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('configuracion'))->with('success', $active ? 'Rol activado.' : 'Rol desactivado.');
    }

    public function deleteRole(int $id): RedirectResponse
    {
        $this->ensureTables();

        $row = $this->db->table('security_roles')->where('id', $id)->get()->getRowArray();
        if (! $row) {
            return redirect()->to(site_url('configuracion'))->with('error', 'Rol no encontrado.');
        }

        $this->db->transStart();
        $this->db->table('security_profiles')
            ->where('role_code', $row['code'])
            ->update([
                'role_code' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        $this->db->table('security_roles')->where('id', $id)->delete();
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to(site_url('configuracion'))->with('error', 'No se pudo eliminar el rol.');
        }

        return redirect()->to(site_url('configuracion'))->with('success', 'Rol eliminado. Los perfiles asociados quedaron sin rol.');
    }

    public function asset(string $type, ?int $id = null)
    {
        $map = [
            'menu' => 'menu_logo',
            'login' => 'login_logo',
            'favicon' => 'favicon',
            'document' => 'document_logo',
        ];

        if (! isset($map[$type])) {
            return $this->response->setStatusCode(404);
        }

        $this->ensureTables();
        $query = $this->db->table('security_institutions')->select($map[$type]);
        if ($id) {
            $query->where('id', $id);
        } else {
            $query->where(['selected' => 1, 'active' => 1]);
        }

        $row = $query->get()->getRowArray();
        $name = $row[$map[$type]] ?? null;
        $path = $name ? WRITEPATH . 'uploads/branding/' . $name : null;

        if (! $path || ! is_file($path) || ! is_readable($path)) {
            return $this->response->setStatusCode(404);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
        ];

        return $this->response
            ->setHeader('Content-Type', $mimes[$ext] ?? 'application/octet-stream')
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setBody((string) file_get_contents($path));
    }

    private function mfaSettings(): array
    {
        $row = $this->db->table('security_mfa_settings')->where('id', 1)->get()->getRowArray();

        return $row ?: [
            'enabled' => 1,
            'email_enabled' => 1,
            'authenticator_enabled' => 1,
            'issuer' => 'Master HDC',
        ];
    }

    private function ensureTables(): void
    {
        if (! $this->db->tableExists('security_institutions')) {
            $this->db->query("CREATE TABLE security_institutions (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(30) NOT NULL UNIQUE,
                name VARCHAR(180) NOT NULL,
                legal_name VARCHAR(200) NULL,
                rut VARCHAR(20) NULL,
                address VARCHAR(220) NULL,
                commune VARCHAR(100) NULL,
                region VARCHAR(100) NULL,
                phone VARCHAR(30) NULL,
                email VARCHAR(160) NULL,
                website VARCHAR(200) NULL,
                primary_color VARCHAR(7) NOT NULL DEFAULT '#1D6FE8',
                menu_logo VARCHAR(180) NULL,
                login_logo VARCHAR(180) NULL,
                favicon VARCHAR(180) NULL,
                document_logo VARCHAR(180) NULL,
                selected TINYINT(1) NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        foreach (self::ASSETS as $field) {
            if (! $this->db->fieldExists($field, 'security_institutions')) {
                $this->db->query("ALTER TABLE security_institutions ADD {$field} VARCHAR(180) NULL AFTER primary_color");
            }
        }

        if ($this->db->table('security_institutions')->countAllResults() === 0) {
            $this->db->table('security_institutions')->insert([
                'code' => 'HDC',
                'name' => 'Hospital de Cauquenes',
                'legal_name' => 'Hospital de Cauquenes',
                'commune' => 'Cauquenes',
                'region' => 'Maule',
                'primary_color' => '#1D6FE8',
                'selected' => 1,
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if (! $this->db->tableExists('security_mfa_settings')) {
            $this->db->query("CREATE TABLE security_mfa_settings (
                id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
                enabled TINYINT(1) NOT NULL DEFAULT 1,
                email_enabled TINYINT(1) NOT NULL DEFAULT 1,
                authenticator_enabled TINYINT(1) NOT NULL DEFAULT 1,
                issuer VARCHAR(120) NOT NULL DEFAULT 'Master HDC',
                updated_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if ($this->db->table('security_mfa_settings')->where('id', 1)->countAllResults() === 0) {
            $this->db->table('security_mfa_settings')->insert([
                'id' => 1,
                'enabled' => 1,
                'email_enabled' => 1,
                'authenticator_enabled' => 1,
                'issuer' => 'Master HDC',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $rolesTableCreated = false;
        if (! $this->db->tableExists('security_roles')) {
            $this->db->query("CREATE TABLE security_roles (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                description VARCHAR(255) NULL,
                icon VARCHAR(60) NOT NULL DEFAULT 'bi-person-fill',
                color VARCHAR(20) NOT NULL DEFAULT '#1D6FE8',
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $rolesTableCreated = true;
        }

        $profilesTableCreated = false;
        if (! $this->db->tableExists('security_profiles')) {
            $this->db->query("CREATE TABLE security_profiles (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description VARCHAR(255) NULL,
                role_code VARCHAR(50) NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                UNIQUE KEY uq_security_profiles_name (name),
                KEY idx_security_profiles_role (role_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $profilesTableCreated = true;
        }

        if (! $this->db->tableExists('security_profile_permissions')) {
            $this->db->query("CREATE TABLE security_profile_permissions (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                profile_id INT UNSIGNED NOT NULL,
                permission_key VARCHAR(120) NOT NULL,
                UNIQUE KEY uq_security_profile_permission (profile_id, permission_key),
                KEY idx_security_profile_permissions_profile (profile_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if ($rolesTableCreated) {
            foreach ($this->defaultRoles() as $role) {
                $role['created_at'] = date('Y-m-d H:i:s');
                $role['updated_at'] = $role['created_at'];
                $this->db->table('security_roles')->insert($role);
            }
        }

        if ($profilesTableCreated) {
            $this->migrateLegacyProfiles();
        }

        if ($profilesTableCreated) {
            foreach ($this->defaultProfiles() as $profile) {
                $exists = $this->db->table('security_profiles')->where('name', $profile['name'])->countAllResults() > 0;
                if ($exists) {
                    continue;
                }

                $permissions = $profile['permissions'];
                unset($profile['permissions']);
                $profile['created_at'] = date('Y-m-d H:i:s');
                $profile['updated_at'] = $profile['created_at'];
                $this->db->table('security_profiles')->insert($profile);
                $this->syncProfilePermissions((int) $this->db->insertID(), $permissions);
            }
        }
    }

    private function availablePermissions(): array
    {
        return [
            'Principal' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
                'master-usuarios' => ['label' => 'Master de usuarios', 'icon' => 'bi-people-fill'],
                'sistemas' => ['label' => 'Sistemas integrados', 'icon' => 'bi-grid-fill'],
            ],
            'Seguridad' => [
                'configuracion' => ['label' => 'Configuracion institucional', 'icon' => 'bi-building-gear'],
                'configuracion/perfiles' => ['label' => 'Perfiles y roles', 'icon' => 'bi-shield-lock-fill'],
                'configuracion/mfa' => ['label' => 'MFA y seguridad', 'icon' => 'bi-key-fill'],
            ],
            'Operaciones' => [
                'usuarios/crear' => ['label' => 'Crear usuarios', 'icon' => 'bi-person-plus-fill'],
                'usuarios/editar' => ['label' => 'Editar usuarios', 'icon' => 'bi-pencil-square'],
                'usuarios/desactivar' => ['label' => 'Activar o desactivar usuarios', 'icon' => 'bi-toggle-on'],
                'usuarios/reset-mfa' => ['label' => 'Restablecer MFA de usuarios', 'icon' => 'bi-arrow-clockwise'],
            ],
            'Administracion tecnica' => [
                'sistemas/crear' => ['label' => 'Crear sistemas', 'icon' => 'bi-plus-square-fill'],
                'sistemas/editar' => ['label' => 'Editar sistemas', 'icon' => 'bi-tools'],
                'sistemas/mfa' => ['label' => 'Politicas MFA por sistema', 'icon' => 'bi-shield-check'],
                'branding' => ['label' => 'Branding institucional', 'icon' => 'bi-palette-fill'],
            ],
        ];
    }

    private function normalizePermissions(array $raw): array
    {
        $allowed = array_keys($this->flatPermissions());
        $permissions = [];
        foreach ($raw as $permission) {
            $permission = trim($this->cleanText((string) $permission, 120));
            if (in_array($permission, $allowed, true) && ! in_array($permission, $permissions, true)) {
                $permissions[] = $permission;
            }
        }

        return $permissions;
    }

    private function flatPermissions(): array
    {
        $flat = [];
        foreach ($this->availablePermissions() as $group) {
            foreach ($group as $key => $item) {
                $flat[$key] = $item;
            }
        }

        return $flat;
    }

    private function getProfilesWithPermissions(): array
    {
        $profiles = $this->db->table('security_profiles p')
            ->select('p.*, r.name AS role_name, r.icon AS role_icon, r.color AS role_color, r.active AS role_active')
            ->join('security_roles r', 'r.code = p.role_code', 'left')
            ->orderBy('p.active', 'DESC')
            ->orderBy('p.name')
            ->get()
            ->getResultArray();

        $permissions = $this->getProfilePermissionsMap();
        foreach ($profiles as &$profile) {
            $profile['permissions'] = $permissions[(int) $profile['id']] ?? [];
        }

        return $profiles;
    }

    private function getProfilePermissionsMap(): array
    {
        $rows = $this->db->table('security_profile_permissions')
            ->orderBy('permission_key')
            ->get()
            ->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $profileId = (int) $row['profile_id'];
            $map[$profileId][] = (string) $row['permission_key'];
        }

        return $map;
    }

    private function syncProfilePermissions(int $profileId, array $permissions): void
    {
        $this->db->table('security_profile_permissions')->where('profile_id', $profileId)->delete();
        if ($permissions === []) {
            return;
        }

        $rows = array_map(static fn(string $permission): array => [
            'profile_id' => $profileId,
            'permission_key' => $permission,
        ], array_values(array_unique($permissions)));

        $this->db->table('security_profile_permissions')->insertBatch($rows);
    }

    private function migrateLegacyProfiles(): void
    {
        if (! $this->db->tableExists('security_profiles_roles')) {
            return;
        }

        $legacyRows = $this->db->table('security_profiles_roles')->get()->getResultArray();
        foreach ($legacyRows as $legacy) {
            $name = $this->cleanText((string) ($legacy['name'] ?? ''), 100);
            if ($name === '' || $this->db->table('security_profiles')->where('name', $name)->countAllResults() > 0) {
                continue;
            }

            $roleCode = $this->cleanSlug((string) ($legacy['code'] ?? ''), 50) ?: null;
            if ($roleCode && ! $this->db->table('security_roles')->where('code', $roleCode)->countAllResults()) {
                $roleCode = null;
            }

            if (in_array($roleCode, ['administrador', 'operador', 'solicitante'], true)) {
                continue;
            }

            $this->db->table('security_profiles')->insert([
                'name' => $name,
                'description' => $this->cleanText((string) ($legacy['description'] ?? ''), 255) ?: null,
                'role_code' => $roleCode,
                'active' => (int) ($legacy['active'] ?? 1),
                'created_at' => $legacy['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $legacyPermissions = array_filter(array_map('trim', explode(',', (string) ($legacy['permissions'] ?? ''))));
            $mapped = [];
            foreach ($legacyPermissions as $permission) {
                if ($permission === 'leer') {
                    $mapped[] = 'dashboard';
                } elseif ($permission === 'escribir') {
                    $mapped[] = 'master-usuarios';
                    $mapped[] = 'usuarios/crear';
                    $mapped[] = 'usuarios/editar';
                } elseif ($permission === 'admin') {
                    $mapped = array_keys($this->flatPermissions());
                }
            }
            $this->syncProfilePermissions((int) $this->db->insertID(), array_values(array_unique($mapped ?: ['dashboard'])));
        }
    }

    private function defaultRoles(): array
    {
        return [
            [
                'code' => 'administrador',
                'name' => 'Administrador',
                'description' => 'Acceso total al sistema, configuracion, perfiles y seguridad.',
                'icon' => 'bi-shield-fill-check',
                'color' => '#dc2626',
                'active' => 1,
            ],
            [
                'code' => 'operador',
                'name' => 'Operador',
                'description' => 'Gestion operativa de usuarios, sistemas y accesos.',
                'icon' => 'bi-person-gear',
                'color' => '#2563eb',
                'active' => 1,
            ],
            [
                'code' => 'directivo',
                'name' => 'Directivo',
                'description' => 'Consulta institucional y supervision de informacion.',
                'icon' => 'bi-briefcase-fill',
                'color' => '#7c3aed',
                'active' => 1,
            ],
            [
                'code' => 'conductor',
                'name' => 'Conductor',
                'description' => 'Rol homologado desde Movilizacion para sistemas integrados.',
                'icon' => 'bi-truck-front-fill',
                'color' => '#059669',
                'active' => 1,
            ],
            [
                'code' => 'solicitante',
                'name' => 'Solicitante',
                'description' => 'Acceso basico de consulta para usuarios solicitantes.',
                'icon' => 'bi-person-lines-fill',
                'color' => '#d97706',
                'active' => 1,
            ],
            [
                'code' => 'enfermera',
                'name' => 'Enfermera',
                'description' => 'Rol clinico homologado desde Movilizacion para validacion centralizada.',
                'icon' => 'bi-heart-pulse-fill',
                'color' => '#0f766e',
                'active' => 1,
            ],
            [
                'code' => 'tens',
                'name' => 'TENS',
                'description' => 'Rol TENS homologado desde Movilizacion para validacion centralizada.',
                'icon' => 'bi-prescription2',
                'color' => '#0891b2',
                'active' => 1,
            ],
        ];
    }

    private function defaultProfiles(): array
    {
        return [
            [
                'name' => 'Perfil Administrador',
                'description' => 'Acceso completo de gestion, configuracion y mantenimiento.',
                'role_code' => 'administrador',
                'permissions' => array_keys($this->flatPermissions()),
                'active' => 1,
            ],
            [
                'name' => 'Perfil Operador',
                'description' => 'Gestion de usuarios, sistemas y accesos sin administracion de roles.',
                'role_code' => 'operador',
                'permissions' => ['dashboard', 'master-usuarios', 'sistemas', 'usuarios/crear', 'usuarios/editar', 'usuarios/desactivar'],
                'active' => 1,
            ],
            [
                'name' => 'Perfil Solicitante',
                'description' => 'Perfil base de consulta para sistemas que validan contra MASTER_HDC.',
                'role_code' => 'solicitante',
                'permissions' => ['dashboard'],
                'active' => 1,
            ],
        ];
    }

    private function storeImage($file, string $prefix): ?string
    {
        if ($file->getSize() > 3 * 1024 * 1024) {
            return null;
        }

        $ext = strtolower($file->getClientExtension());
        $allowed = ['png', 'jpg', 'jpeg', 'webp', 'ico'];
        if (! in_array($ext, $allowed, true)) {
            return null;
        }

        $mime = $file->getMimeType();
        $mimes = ['image/png', 'image/jpeg', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'];
        if ($ext === 'ico') {
            $mimes[] = 'application/octet-stream';
        }
        if (! in_array($mime, $mimes, true)) {
            return null;
        }

        $dir = WRITEPATH . 'uploads/branding';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $name = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $file->move($dir, $name);

        return $name;
    }
}
