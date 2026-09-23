<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialSeeder extends Seeder
{
    public function run(): void
    {
        $db = db_connect('master_usuarios');

        $exists = $db->table('sistemas_hdc')->where('SLUG', 'master_hdc')->countAllResults();
        if ($exists === 0) {
            $db->table('sistemas_hdc')->insert([
                'NOMBRE' => 'Master HDC',
                'SLUG' => 'master_hdc',
                'DESCRIPCION' => 'Administracion central de usuarios, MFA y accesos HDC',
                'URL' => rtrim((string) config('App')->baseURL, '/') . '/',
                'ACTIVO' => 1,
                'CREATED_AT' => date('Y-m-d H:i:s'),
                'UPDATED_AT' => date('Y-m-d H:i:s'),
            ]);
        }

        $sistema = $db->table('sistemas_hdc')->where('SLUG', 'master_hdc')->get()->getRowArray();
        if ($sistema) {
            $usuarios = $db->table('user')->get()->getResultArray();
            foreach ($usuarios as $usuario) {
                $exists = $db->table('usuario_sistemas')
                    ->where('USER_ID', $usuario['ID'])
                    ->where('SISTEMA_ID', $sistema['ID'])
                    ->countAllResults();

                if ($exists === 0) {
                    $db->table('usuario_sistemas')->insert([
                        'USER_ID' => $usuario['ID'],
                        'SISTEMA_ID' => $sistema['ID'],
                        'ACTIVO' => 1,
                        'CREATED_AT' => date('Y-m-d H:i:s'),
                        'UPDATED_AT' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }
}
