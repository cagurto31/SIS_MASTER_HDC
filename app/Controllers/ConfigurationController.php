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
