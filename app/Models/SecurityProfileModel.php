<?php

namespace App\Models;

use CodeIgniter\Model;

class SecurityProfileModel extends Model
{
    protected $DBGroup = 'master_usuarios';
    protected $table = 'security_profiles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    public function assignmentReady(): bool
    {
        return $this->db->tableExists('security_profiles')
            && $this->db->tableExists('security_roles')
            && $this->db->fieldExists('MASTER_PERFIL_ID', 'user');
    }

    public function catalog(): array
    {
        if (! $this->assignmentReady()) {
            return [];
        }

        $rows = $this->db->table('security_profiles p')
            ->select('p.*, r.name AS role_name, r.active AS role_active')
            ->join('security_roles r', 'r.code = p.role_code', 'left')
            ->orderBy('p.name')->get()->getResultArray();
        $profiles = [];
        foreach ($rows as $row) {
            $row['assignable'] = (int) $row['active'] === 1
                && (empty($row['role_code']) || (int) $row['role_active'] === 1);
            $profiles[(int) $row['id']] = $row;
        }

        return $profiles;
    }

    public function validateAssignment(?int $profileId, ?int $currentId = null): ?string
    {
        if (! $this->assignmentReady()) {
            return 'La asignacion de perfiles requiere actualizar la base de datos de Master HDC.';
        }
        if ($profileId === null) {
            return null;
        }
        $profile = $this->catalog()[$profileId] ?? null;
        if (! $profile) {
            return 'El perfil seleccionado no existe.';
        }
        if (! $profile['assignable'] && $profileId !== $currentId) {
            return 'Seleccione un perfil activo con un rol activo.';
        }

        return null;
    }

    public function assignedUserCount(int $profileId): int
    {
        if (! $this->db->fieldExists('MASTER_PERFIL_ID', 'user')) {
            return 0;
        }

        return $this->db->table('user')->where('MASTER_PERFIL_ID', $profileId)->countAllResults();
    }
}
