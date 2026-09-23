<?php

namespace App\Controllers;

use App\Models\SistemaModel;
use CodeIgniter\HTTP\RedirectResponse;

class SistemasController extends BaseController
{
    private SistemaModel $model;

    public function __construct()
    {
        $this->model = new SistemaModel();
    }

    public function index(): string
    {
        $db = db_connect('master_usuarios');

        return view('sistemas/index', [
            'titulo' => 'Sistemas HDC',
            'usuario' => $this->usuario(),
            'sistemas' => $db->table('sistemas_hdc s')
                ->select('s.*, m.MFA_ENABLED, m.EMAIL_ENABLED, m.AUTHENTICATOR_ENABLED, m.ISSUER')
                ->join('sistema_mfa_settings m', 'm.SISTEMA_ID = s.ID', 'left')
                ->orderBy('s.NOMBRE')
                ->get()
                ->getResultArray(),
        ]);
    }

    public function crear(): string
    {
        return view('sistemas/form', [
            'titulo' => 'Nuevo sistema',
            'usuario' => $this->usuario(),
            'sistema' => null,
        ]);
    }

    public function editar(int $id)
    {
        $sistema = $this->model->find($id);
        if (! $sistema) {
            return redirect()->to('/sistemas')->with('error', 'Sistema no encontrado.');
        }

        return view('sistemas/form', [
            'titulo' => 'Editar sistema',
            'usuario' => $this->usuario(),
            'sistema' => $sistema,
        ]);
    }

    public function guardar(?int $id = null)
    {
        $rules = [
            'NOMBRE' => 'required|max_length[80]',
            'SLUG' => 'required|alpha_dash|max_length[60]',
            'URL' => 'permit_empty|valid_url_strict|max_length[255]',
            'DESCRIPCION' => 'permit_empty|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $data = [
            'NOMBRE' => $this->cleanText((string) $this->request->getPost('NOMBRE'), 80),
            'SLUG' => $this->cleanSlug((string) $this->request->getPost('SLUG'), 60),
            'URL' => $this->cleanText((string) $this->request->getPost('URL'), 255) ?: null,
            'DESCRIPCION' => $this->cleanText((string) $this->request->getPost('DESCRIPCION'), 255) ?: null,
            'ACTIVO' => $this->request->getPost('ACTIVO') ? 1 : 0,
        ];

        if ($id) {
            $this->model->update($id, $data);
            return redirect()->to('/sistemas')->with('success', 'Sistema actualizado.');
        }

        $this->model->insert($data);
        $this->crearConfiguracionMfa((int) $this->model->getInsertID(), $data['NOMBRE']);
        return redirect()->to('/sistemas')->with('success', 'Sistema creado.');
    }

    public function toggle(int $id)
    {
        $sistema = $this->model->find($id);
        if (! $sistema) {
            return redirect()->to('/sistemas')->with('error', 'Sistema no encontrado.');
        }

        $this->model->update($id, ['ACTIVO' => (int) ! (int) $sistema['ACTIVO']]);

        return redirect()->to('/sistemas')->with('success', 'Estado del sistema actualizado.');
    }

    public function guardarMfa(int $id): RedirectResponse
    {
        $sistema = $this->model->find($id);
        if (! $sistema) {
            return redirect()->to('/sistemas')->with('error', 'Sistema no encontrado.');
        }

        $mfa = $this->request->getPost('MFA_ENABLED') ? 1 : 0;
        $email = $this->request->getPost('EMAIL_ENABLED') ? 1 : 0;
        $authenticator = $this->request->getPost('AUTHENTICATOR_ENABLED') ? 1 : 0;

        if ($mfa && ! $email && ! $authenticator) {
            return redirect()->to('/sistemas')->with('error', 'Debe seleccionar correo, Authenticator o ambos para activar MFA.');
        }

        $issuer = $this->cleanText((string) $this->request->getPost('ISSUER'), 120) ?: (string) $sistema['NOMBRE'];
        $data = [
            'SISTEMA_ID' => $id,
            'MFA_ENABLED' => $mfa,
            'EMAIL_ENABLED' => $email,
            'AUTHENTICATOR_ENABLED' => $authenticator,
            'ISSUER' => mb_substr($issuer, 0, 120),
            'UPDATED_AT' => date('Y-m-d H:i:s'),
        ];

        $db = db_connect('master_usuarios');
        $exists = $db->table('sistema_mfa_settings')->where('SISTEMA_ID', $id)->countAllResults() > 0;
        if ($exists) {
            $db->table('sistema_mfa_settings')->where('SISTEMA_ID', $id)->update($data);
        } else {
            $db->table('sistema_mfa_settings')->insert($data);
        }

        return redirect()->to('/sistemas')->with('success', 'Configuracion MFA de ' . $sistema['NOMBRE'] . ' actualizada.');
    }

    private function crearConfiguracionMfa(int $sistemaId, string $nombre): void
    {
        db_connect('master_usuarios')->table('sistema_mfa_settings')->ignore(true)->insert([
            'SISTEMA_ID' => $sistemaId,
            'MFA_ENABLED' => 0,
            'EMAIL_ENABLED' => 1,
            'AUTHENTICATOR_ENABLED' => 1,
            'ISSUER' => mb_substr($nombre, 0, 120),
            'UPDATED_AT' => date('Y-m-d H:i:s'),
        ]);
    }
}
