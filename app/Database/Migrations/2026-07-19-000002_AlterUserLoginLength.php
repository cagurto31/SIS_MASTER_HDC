<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterUserLoginLength extends Migration
{
    protected $DBGroup = 'master_usuarios';

    public function up(): void
    {
        if ($this->db->fieldExists('USER', 'user')) {
            $this->forge->modifyColumn('user', [
                'USER' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('USER', 'user')) {
            $this->forge->modifyColumn('user', [
                'USER' => [
                    'type' => 'VARCHAR',
                    'constraint' => 15,
                    'null' => false,
                ],
            ]);
        }
    }
}
