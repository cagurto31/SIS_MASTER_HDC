<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioSistemaModel extends Model
{
    protected $DBGroup = 'master_usuarios';
    protected $table = 'usuario_sistemas';
    protected $primaryKey = 'ID';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'CREATED_AT';
    protected $updatedField = 'UPDATED_AT';
    protected $allowedFields = [
        'USER_ID', 'SISTEMA_ID', 'ACTIVO',
    ];

    public function accesosUsuario(int $userId): array
    {
        return $this->select('usuario_sistemas.*, sistemas_hdc.NOMBRE, sistemas_hdc.SLUG, sistemas_hdc.URL')
            ->join('sistemas_hdc', 'sistemas_hdc.ID = usuario_sistemas.SISTEMA_ID')
            ->where('usuario_sistemas.USER_ID', $userId)
            ->orderBy('sistemas_hdc.NOMBRE')
            ->findAll();
    }

    public function reemplazarAccesos(int $userId, array $accesos): void
    {
        $db = $this->db;
        $db->transStart();
        $this->where('USER_ID', $userId)->delete();

        foreach ($accesos as $acceso) {
            if (empty($acceso['SISTEMA_ID'])) {
                continue;
            }

            $this->insert([
                'USER_ID' => $userId,
                'SISTEMA_ID' => (int) $acceso['SISTEMA_ID'],
                'ACTIVO' => ! empty($acceso['ACTIVO']) ? 1 : 0,
            ]);
        }

        $db->transComplete();
    }
}
