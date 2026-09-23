<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSistemaMfaSettings extends Migration
{
    protected $DBGroup = 'master_usuarios';

    public function up(): void
    {
        if (! $this->db->tableExists('sistema_mfa_settings')) {
            $this->db->query("CREATE TABLE sistema_mfa_settings (
                SISTEMA_ID INT UNSIGNED NOT NULL,
                MFA_ENABLED TINYINT(1) NOT NULL DEFAULT 0,
                EMAIL_ENABLED TINYINT(1) NOT NULL DEFAULT 1,
                AUTHENTICATOR_ENABLED TINYINT(1) NOT NULL DEFAULT 1,
                ISSUER VARCHAR(120) NOT NULL DEFAULT 'Master HDC',
                UPDATED_AT DATETIME NULL,
                PRIMARY KEY (SISTEMA_ID),
                CONSTRAINT fk_sistema_mfa_sistema FOREIGN KEY (SISTEMA_ID)
                    REFERENCES sistemas_hdc(ID) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        $legacy = $this->db->tableExists('security_mfa_settings')
            ? $this->db->table('security_mfa_settings')->where('id', 1)->get()->getRowArray()
            : null;

        foreach ($this->db->table('sistemas_hdc')->select('ID, NOMBRE')->get()->getResultArray() as $sistema) {
            $this->db->table('sistema_mfa_settings')->ignore(true)->insert([
                'SISTEMA_ID' => (int) $sistema['ID'],
                'MFA_ENABLED' => (int) ($legacy['enabled'] ?? 0),
                'EMAIL_ENABLED' => (int) ($legacy['email_enabled'] ?? 1),
                'AUTHENTICATOR_ENABLED' => (int) ($legacy['authenticator_enabled'] ?? 1),
                'ISSUER' => mb_substr((string) $sistema['NOMBRE'], 0, 120),
                'UPDATED_AT' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('sistema_mfa_settings', true);
    }
}
