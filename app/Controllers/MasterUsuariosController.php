<?php

namespace App\Controllers;

use App\Models\MasterUsuarioModel;
use App\Models\SistemaModel;
use App\Models\UsuarioSistemaModel;

class MasterUsuariosController extends BaseController
{
    private MasterUsuarioModel $model;
    private const PER_PAGE = 20;

    public function __construct()
    {
        $this->model = new MasterUsuarioModel();
    }

    public function index(): string
    {
        $q = $this->cleanText((string) $this->request->getGet('q'), 80);
        $sistema = $this->cleanSlug((string) $this->request->getGet('sistema'), 60);
        $sistemas = (new SistemaModel())->activos();
        $slugs = array_column($sistemas, 'SLUG');
        if ($sistema !== '' && ! in_array($sistema, $slugs, true)) {
            $sistema = '';
        }
        $usuarios = $this->model->getPaginado(self::PER_PAGE, $q, $sistema);

        return view('master_usuarios/index', [
            'titulo' => 'Personal HDC',
            'usuario' => $this->usuario(),
            'usuarios' => $usuarios,
            'pager' => $this->model->pager,
            'q' => $q,
            'sistemaFiltro' => $sistema,
            'sistemas' => $sistemas,
        ]);
    }

    public function crear(): string
    {
        return view('master_usuarios/form', [
            'titulo' => 'Nuevo usuario',
            'usuario' => $this->usuario(),
            'mu' => null,
            'sistemas' => (new SistemaModel())->activos(),
            'accesos' => [],
            'centros' => $this->centrosCosto(),
            'servicios' => $this->servicios(),
        ]);
    }

    public function editar(int $id)
    {
        $mu = $this->model->find($id);
        if (! $mu) {
            return redirect()->to('/master-usuarios')->with('error', 'Usuario no encontrado.');
        }

        return view('master_usuarios/form', [
            'titulo' => 'Editar usuario',
            'usuario' => $this->usuario(),
            'mu' => $mu,
            'sistemas' => (new SistemaModel())->activos(),
            'accesos' => (new UsuarioSistemaModel())->accesosUsuario($id),
            'centros' => $this->centrosCosto(),
            'servicios' => $this->servicios(),
        ]);
    }

    public function guardar(?int $id = null)
    {
        $rules = [
            'NOMBRE' => 'required|max_length[60]',
            'APELLIDO_PATERNO' => 'required|max_length[60]',
            'APELLIDO_MATERNO' => 'required|max_length[60]',
            'CORREO' => 'permit_empty|valid_email|max_length[50]',
            'CARGO' => 'permit_empty|max_length[40]',
            'TELEFONO' => 'permit_empty|integer|max_length[20]',
            'USER' => 'required|alpha_dash|max_length[20]',
            'DEPARTAMENTO' => 'permit_empty|max_length[60]',
            'UNIDAD' => 'permit_empty|max_length[60]',
            'CENTRO_COSTO_ID' => 'permit_empty|integer',
            'SERVICIO_ID' => 'permit_empty|integer',
            'PASSWORD' => $id ? 'permit_empty|min_length[8]|max_length[10]' : 'required|min_length[8]|max_length[10]',
        ];

        if (! $id) {
            $rules['RUT'] = 'required|max_length[12]|regex_match[/^[0-9.]+$/]';
            $rules['DV'] = 'required|regex_match[/^[0-9Kk]$/]';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $post = $this->request->getPost();
        $accesos = $this->normalizarAccesos($post['ACCESOS'] ?? []);

        $data = [
            'NOMBRE' => $this->cleanText($post['NOMBRE'] ?? '', 60),
            'APELLIDO_PATERNO' => $this->cleanText($post['APELLIDO_PATERNO'] ?? '', 60),
            'APELLIDO_MATERNO' => $this->cleanText($post['APELLIDO_MATERNO'] ?? '', 60),
            'CORREO' => mb_strtolower($this->cleanText($post['CORREO'] ?? '', 50)) ?: null,
            'CARGO' => $this->cleanText($post['CARGO'] ?? '', 40) ?: null,
            'TELEFONO' => ($t = trim((string) ($post['TELEFONO'] ?? ''))) !== '' ? (int) $t : null,
            'USER' => $this->cleanSlug($post['USER'] ?? '', 20),
            'DEPARTAMENTO' => $this->cleanText($post['DEPARTAMENTO'] ?? '', 60) ?: null,
            'UNIDAD' => $this->cleanText($post['UNIDAD'] ?? '', 60) ?: null,
            'CENTRO_COSTO_ID' => ($centroId = trim((string) ($post['CENTRO_COSTO_ID'] ?? ''))) !== '' ? (int) $centroId : null,
            'SERVICIO_ID' => ($servicioId = trim((string) ($post['SERVICIO_ID'] ?? ''))) !== '' ? (int) $servicioId : null,
            'SISTEMA' => $this->resumenSistemas($accesos),
            'MFA_REQUIRED' => $this->request->getPost('MFA_REQUIRED') ? 1 : 0,
            'ACTIVO' => $this->request->getPost('ACTIVO') ? 1 : 0,
        ];

        if (! empty($data['SERVICIO_ID'])) {
            $data['CENTRO_COSTO_ID'] = $this->centroCostoIdPorServicio((int) $data['SERVICIO_ID']) ?? $data['CENTRO_COSTO_ID'];
        }

        if (! $id) {
            $data['RUT'] = $this->cleanDigits($post['RUT'] ?? '', 12);
            $data['DV'] = strtoupper($this->cleanText($post['DV'] ?? '', 1));
        }

        $pw = trim((string) ($post['PASSWORD'] ?? ''));
        if ($pw !== '') {
            $data['PASSWORD'] = password_hash($pw, PASSWORD_BCRYPT);
            $data['PASSWORD_CHANGED_AT'] = date('Y-m-d H:i:s');
        }

        $accessModel = new UsuarioSistemaModel();

        if ($id) {
            $this->model->update($id, $data);
            $accessModel->reemplazarAccesos($id, $accesos);

            return redirect()->to('/master-usuarios')->with('success', 'Usuario actualizado.');
        }

        $id = (int) $this->model->insert($data, true);
        $accessModel->reemplazarAccesos($id, $accesos);

        return redirect()->to('/master-usuarios')->with('success', 'Usuario creado.');
    }

    public function toggleActivo(int $id)
    {
        $mu = $this->model->find($id);
        if (! $mu) {
            return redirect()->to('/master-usuarios')->with('error', 'Usuario no encontrado.');
        }

        $nuevo = $mu['ACTIVO'] ? 0 : 1;
        $this->model->update($id, ['ACTIVO' => $nuevo]);

        return redirect()->to('/master-usuarios')->with('success', $nuevo ? 'Usuario activado.' : 'Usuario desactivado.');
    }

    public function resetMfa(int $id)
    {
        $mu = $this->model->find($id);
        if (! $mu) {
            return redirect()->to('/master-usuarios')->with('error', 'Usuario no encontrado.');
        }

        $this->model->update($id, [
            'MFA_TOTP_SECRET' => null,
            'MFA_TOTP_ENABLED' => 0,
            'MFA_REQUIRED' => 1,
        ]);

        return redirect()->to('/master-usuarios')->with('success', 'MFA reiniciado. El usuario debera configurarlo en su proximo ingreso.');
    }

    private function normalizarAccesos(array $raw): array
    {
        $accesos = [];

        foreach ($raw as $sistemaId => $item) {
            if (empty($item['ACTIVO'])) {
                continue;
            }

            $accesos[] = [
                'SISTEMA_ID' => (int) $sistemaId,
                'ACTIVO' => 1,
            ];
        }

        return $accesos;
    }

    private function resumenSistemas(array $accesos): ?string
    {
        if ($accesos === []) {
            return null;
        }

        $ids = array_column($accesos, 'SISTEMA_ID');
        $sistemas = (new SistemaModel())->whereIn('ID', $ids)->findAll();

        return implode(',', array_column($sistemas, 'SLUG')) ?: null;
    }

    private function centrosCosto(): array
    {
        try {
            return db_connect('master_usuarios')
                ->table('centro_costo')
                ->orderBy('nombre_centro')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function servicios(): array
    {
        try {
            return db_connect('master_usuarios')
                ->table('servicios')
                ->orderBy('nom_servicio')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function centroCostoIdPorServicio(int $servicioId): ?int
    {
        try {
            $row = db_connect('master_usuarios')
                ->table('servicios s')
                ->select('cc.id')
                ->join('centro_costo cc', 'cc.codigo_centro = s.codigo_centro')
                ->where('s.cod_servicios', $servicioId)
                ->get()
                ->getRowArray();

            return $row ? (int) $row['id'] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

}
