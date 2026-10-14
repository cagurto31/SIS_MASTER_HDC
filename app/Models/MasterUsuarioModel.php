<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterUsuarioModel extends Model
{
    protected $DBGroup        = 'master_usuarios';
    protected $table          = 'user';
    protected $primaryKey     = 'ID';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    protected $beforeInsert = ['normalizarNombres'];
    protected $beforeUpdate = ['normalizarNombres'];

    protected function normalizarNombres(array $event): array
    {
        foreach (['NOMBRE', 'APELLIDO_PATERNO', 'APELLIDO_MATERNO'] as $campo) {
            if (isset($event['data'][$campo])) {
                $event['data'][$campo] = mb_strtoupper($event['data'][$campo], 'UTF-8');
            }
        }

        return $event;
    }

    protected $allowedFields = [
        'RUT', 'DV', 'NOMBRE', 'APELLIDO_PATERNO', 'APELLIDO_MATERNO',
        'CORREO', 'CARGO', 'TELEFONO', 'PASSWORD', 'USER',
        'DEPARTAMENTO', 'UBICACION', 'UNIDAD', 'SISTEMA', 'ACTIVO',
        'MFA_REQUIRED', 'MFA_TOTP_SECRET', 'MFA_TOTP_ENABLED',
        'LAST_LOGIN_AT', 'PASSWORD_CHANGED_AT',
        'FAILED_LOGIN_ATTEMPTS', 'LOCKED_UNTIL',
        'CENTRO_COSTO_ID', 'SERVICIO_ID',
        'MASTER_PERFIL_ID',
    ];

    public function getPaginado(int $perPage, string $q = '', string $sistema = ''): array
    {
        $builder = $this
            ->select("user.*, COALESCE((
                SELECT GROUP_CONCAT(s.SLUG ORDER BY s.NOMBRE SEPARATOR ',')
                FROM usuario_sistemas us
                INNER JOIN sistemas_hdc s ON s.ID = us.SISTEMA_ID
                WHERE us.USER_ID = user.ID
                  AND us.ACTIVO = 1
                  AND s.ACTIVO = 1
            ), '') AS SISTEMA", false)
            ->orderBy('user.APELLIDO_PATERNO');

        if ($q !== '') {
            $builder->groupStart()
                    ->like('user.NOMBRE', $q)
                    ->orLike('user.APELLIDO_PATERNO', $q)
                    ->orLike('user.RUT', $q)
                    ->orLike('user.CORREO', $q)
                    ->groupEnd();
        }
        if ($sistema !== '') {
            $builder->where("EXISTS (
                SELECT 1
                FROM usuario_sistemas usf
                INNER JOIN sistemas_hdc sf ON sf.ID = usf.SISTEMA_ID
                WHERE usf.USER_ID = user.ID
                  AND usf.ACTIVO = 1
                  AND sf.ACTIVO = 1
                  AND sf.SLUG = " . $this->db->escape($sistema) . "
            )", null, false);
        }
        return $builder->paginate($perPage, 'default');
    }
}
