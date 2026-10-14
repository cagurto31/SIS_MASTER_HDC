<?php

namespace App\Libraries;

/** Identificador del propio Master HDC, compartido por acceso y MFA. */
final class MasterSystem
{
    public static function slug(): string
    {
        $slug = trim((string) env('security.systemSlug', 'master_hdc'));
        if ($slug === '' || ! preg_match('/^[a-z0-9_-]+$/D', $slug)) {
            throw new \RuntimeException('Configure security.systemSlug con el SLUG de Master HDC.');
        }
        return $slug;
    }
}
