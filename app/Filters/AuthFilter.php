<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    private const IDLE_TIMEOUT_SECONDS = 1800;

    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if (! $session->get('usuario_id')) {
            return redirect()->to('/login')->with('error', 'Debe iniciar sesion');
        }

        $lastActivity = (int) $session->get('last_activity_at');
        if ($lastActivity > 0 && (time() - $lastActivity) > self::IDLE_TIMEOUT_SECONDS) {
            $session->destroy();

            return redirect()->to('/login')->with('error', 'La sesion expiro. Inicia sesion nuevamente.');
        }

        $session->set('last_activity_at', time());
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
