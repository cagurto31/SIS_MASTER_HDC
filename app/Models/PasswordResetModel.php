<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetModel extends Model
{
    protected $DBGroup       = 'master_usuarios';
    protected $table         = 'password_resets';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['email', 'token_hash', 'expires_at', 'used', 'created_at'];

    public function crearToken(string $email, int $ttlSegundos = 3600): string
    {
        $this->ensureTable();

        $token = bin2hex(random_bytes(32));
        $this->db->table($this->table)->where('email', $email)->update(['used' => 1]);

        $this->insert([
            'email'      => $email,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlSegundos),
            'used'       => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    public function tokenValido(string $token): ?array
    {
        $this->ensureTable();

        $row = $this->where('token_hash', hash('sha256', $token))
            ->where('used', 0)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->first();

        return $row ?: null;
    }

    public function marcarUsado(int $id): void
    {
        $this->ensureTable();
        $this->update($id, ['used' => 1]);
    }

    private function ensureTable(): void
    {
        if ($this->db->tableExists($this->table)) {
            return;
        }

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `password_resets` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `email` VARCHAR(190) NOT NULL,
                `token_hash` CHAR(64) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `used` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_password_resets_email` (`email`),
                UNIQUE KEY `idx_password_resets_token_hash` (`token_hash`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
}
