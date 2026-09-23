<?php

namespace App\Models;

use CodeIgniter\Model;

class SistemaModel extends Model
{
    protected $DBGroup = 'master_usuarios';
    protected $table = 'sistemas_hdc';
    protected $primaryKey = 'ID';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'CREATED_AT';
    protected $updatedField = 'UPDATED_AT';
    protected $allowedFields = [
        'NOMBRE', 'SLUG', 'DESCRIPCION', 'URL', 'ACTIVO',
    ];

    public function activos(): array
    {
        return $this->where('ACTIVO', 1)->orderBy('NOMBRE')->findAll();
    }
}
