<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLoginLockoutToUsers extends Migration
{
    protected $DBGroup = 'master_usuarios';

    public function up(): void
    {
        $fields = [
            'FAILED_LOGIN_ATTEMPTS' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'PASSWORD_CHANGED_AT'],
            'LOCKED_UNTIL' => ['type' => 'DATETIME', 'null' => true, 'after' => 'FAILED_LOGIN_ATTEMPTS'],
        ];

        foreach ($fields as $name => $field) {
            if (! $this->db->fieldExists($name, 'user')) {
                $this->forge->addColumn('user', [$name => $field]);
            }
        }
    }

    public function down(): void
    {
        foreach (['LOCKED_UNTIL', 'FAILED_LOGIN_ATTEMPTS'] as $name) {
            if ($this->db->fieldExists($name, 'user')) {
                $this->forge->dropColumn('user', $name);
            }
        }
    }
}
