<?php

namespace App\Controllers;

use App\Models\MasterUsuarioModel;
use App\Models\SistemaModel;
use App\Models\UsuarioSistemaModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $usuarios = new MasterUsuarioModel();
        $sistemas = new SistemaModel();
        $accesos = new UsuarioSistemaModel();
        $db = db_connect('master_usuarios');

        $usuariosDetalle = (new MasterUsuarioModel())
            ->select('ID, RUT, DV, NOMBRE, APELLIDO_PATERNO, APELLIDO_MATERNO, USER, CORREO, ACTIVO, MFA_REQUIRED, MFA_TOTP_ENABLED')
            ->orderBy('APELLIDO_PATERNO')
            ->orderBy('APELLIDO_MATERNO')
            ->orderBy('NOMBRE')
            ->findAll();

        $sistemasDetalle = (new SistemaModel())
            ->select('ID, NOMBRE, SLUG, DESCRIPCION, URL, ACTIVO')
            ->orderBy('NOMBRE')
            ->findAll();

        $accesosDetalle = $db->table('usuario_sistemas us')
            ->select('us.ID, us.ACTIVO, us.CREATED_AT, u.RUT, u.DV, u.NOMBRE, u.APELLIDO_PATERNO, u.APELLIDO_MATERNO, u.USER, s.NOMBRE AS SISTEMA_NOMBRE, s.SLUG')
            ->join('user u', 'u.ID = us.USER_ID')
            ->join('sistemas_hdc s', 's.ID = us.SISTEMA_ID')
            ->where('us.ACTIVO', 1)
            ->orderBy('s.NOMBRE')
            ->orderBy('u.APELLIDO_PATERNO')
            ->get()
            ->getResultArray();

        $mfaPendientesDetalle = (new MasterUsuarioModel())
            ->select('ID, RUT, DV, NOMBRE, APELLIDO_PATERNO, APELLIDO_MATERNO, USER, CORREO, MFA_REQUIRED, MFA_TOTP_ENABLED')
            ->where('MFA_REQUIRED', 1)
            ->where('MFA_TOTP_ENABLED', 0)
            ->orderBy('APELLIDO_PATERNO')
            ->orderBy('APELLIDO_MATERNO')
            ->orderBy('NOMBRE')
            ->findAll();

        return view('dashboard/index', [
            'usuario' => $this->usuario(),
            'titulo' => 'Inicio',
            'total_usuarios' => $usuarios->countAllResults(),
            'usuarios_activos' => $usuarios->where('ACTIVO', 1)->countAllResults(),
            'usuarios_inactivos' => $usuarios->where('ACTIVO', 0)->countAllResults(),
            'sistemas_activos' => $sistemas->where('ACTIVO', 1)->countAllResults(),
            'accesos_activos' => $accesos->where('ACTIVO', 1)->countAllResults(),
            'mfa_pendientes' => $usuarios->where('MFA_REQUIRED', 1)->where('MFA_TOTP_ENABLED', 0)->countAllResults(),
            'usuarios_detalle' => $usuariosDetalle,
            'sistemas_detalle' => $sistemasDetalle,
            'accesos_detalle' => $accesosDetalle,
            'mfa_pendientes_detalle' => $mfaPendientesDetalle,
        ]);
    }
}
