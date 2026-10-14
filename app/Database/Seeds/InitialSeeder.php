<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialSeeder extends Seeder
{
    private const MASTER_HDC_ADMIN_USERS = ['cagurtoc', 'jsepulvedar'];

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
            $sistemaId = (int) $sistema['ID'];
            $rolExists = $db->fieldExists('ROL', 'usuario_sistemas');
            $permisosExists = $db->fieldExists('PERMISOS', 'usuario_sistemas');

            $adminUsers = array_map('mb_strtolower', self::MASTER_HDC_ADMIN_USERS);
            $usuarios = $db->table('user')->whereIn('LOWER(USER)', $adminUsers)->get()->getResultArray();
            $adminIds = array_map(static fn (array $usuario): int => (int) $usuario['ID'], $usuarios);

            $deactivate = $db->table('usuario_sistemas')->where('SISTEMA_ID', $sistemaId);
            if ($adminIds !== []) {
                $deactivate->whereNotIn('USER_ID', $adminIds);
            }
            $deactivate->update([
                'ACTIVO' => 0,
                'UPDATED_AT' => date('Y-m-d H:i:s'),
            ]);

            foreach ($usuarios as $usuario) {
                $exists = $db->table('usuario_sistemas')
                    ->where('USER_ID', $usuario['ID'])
                    ->where('SISTEMA_ID', $sistemaId)
                    ->countAllResults();

                $data = [
                    'USER_ID' => $usuario['ID'],
                    'SISTEMA_ID' => $sistemaId,
                    'ACTIVO' => 1,
                    'UPDATED_AT' => date('Y-m-d H:i:s'),
                ];

                if ($rolExists) {
                    $data['ROL'] = 'administrador';
                }

                if ($permisosExists) {
                    $data['PERMISOS'] = 'leer,escribir,admin';
                }

                if ($exists === 0) {
                    $data['CREATED_AT'] = date('Y-m-d H:i:s');
                    $db->table('usuario_sistemas')->insert($data);
                } else {
                    $db->table('usuario_sistemas')
                        ->where('USER_ID', $usuario['ID'])
                        ->where('SISTEMA_ID', $sistemaId)
                        ->update($data);
                }
            }
        }
    }
}
