<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMasterHdcIdentitySchema extends Migration
{
    protected $DBGroup = 'master_usuarios';

    public function up(): void
    {
        foreach ($this->userColumns() as $name => $field) {
            if (! $this->db->fieldExists($name, 'user')) {
                $this->forge->addColumn('user', [$name => $field]);
            }
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

        $this->db->table('security_mfa_settings')->ignore(true)->insert([
            'id' => 1,
            'enabled' => 1,
            'email_enabled' => 1,
            'authenticator_enabled' => 1,
            'issuer' => 'Master HDC',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

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

        $this->db->table('security_institutions')->ignore(true)->insert([
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

        if (! $this->db->tableExists('sistemas_hdc')) {
            $this->forge->addField([
                'ID' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'NOMBRE' => ['type' => 'VARCHAR', 'constraint' => 80],
                'SLUG' => ['type' => 'VARCHAR', 'constraint' => 60],
                'DESCRIPCION' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'URL' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'ACTIVO' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'CREATED_AT' => ['type' => 'DATETIME', 'null' => true],
                'UPDATED_AT' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('ID', true);
            $this->forge->addUniqueKey('SLUG');
            $this->forge->createTable('sistemas_hdc');
        }

        if (! $this->db->tableExists('usuario_sistemas')) {
            $this->forge->addField([
                'ID' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'USER_ID' => ['type' => 'BIGINT'],
                'SISTEMA_ID' => ['type' => 'INT', 'unsigned' => true],
                'ACTIVO' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'CREATED_AT' => ['type' => 'DATETIME', 'null' => true],
                'UPDATED_AT' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('ID', true);
            $this->forge->addUniqueKey(['USER_ID', 'SISTEMA_ID']);
            $this->forge->addKey('USER_ID');
            $this->forge->addForeignKey('SISTEMA_ID', 'sistemas_hdc', 'ID', '', 'CASCADE');
            $this->forge->createTable('usuario_sistemas');
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('usuario_sistemas', true);
        $this->forge->dropTable('sistemas_hdc', true);
    }

    private function userColumns(): array
    {
        return [
            'MFA_REQUIRED' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'MFA_TOTP_SECRET' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'MFA_TOTP_ENABLED' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'LAST_LOGIN_AT' => ['type' => 'DATETIME', 'null' => true],
            'PASSWORD_CHANGED_AT' => ['type' => 'DATETIME', 'null' => true],
        ];
    }
}
