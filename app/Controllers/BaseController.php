<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    protected $helpers = ['form', 'url'];
    protected $session;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->session = session();
    }

    protected function usuario(): ?array
    {
        $id = $this->session->get('usuario_id');
        if (! $id) return null;

        $nombre = $this->session->get('nombre');
        try {
            $user = db_connect('master_usuarios')
                ->table('user')
                ->select('NOMBRE, APELLIDO_PATERNO, APELLIDO_MATERNO')
                ->where('ID', $id)
                ->get()
                ->getRowArray();

            if ($user) {
                $nombres = preg_split('/\s+/', trim((string) ($user['NOMBRE'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
                $nombre = trim(implode(' ', array_filter([
                    $nombres[0] ?? '',
                    trim((string) ($user['APELLIDO_PATERNO'] ?? '')),
                    trim((string) ($user['APELLIDO_MATERNO'] ?? '')),
                ]))) ?: $nombre;
            }
        } catch (\Throwable $e) {
        }

        return [
            'id'          => $id,
            'nombre'      => $nombre,
            'email'       => $this->session->get('email'),
            'hospital_id' => $this->session->get('hospital_id'),
        ];
    }

    protected function cleanText(?string $value, int $maxLength = 255): string
    {
        $value = trim(strip_tags((string) $value));
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return mb_substr(trim($value), 0, $maxLength);
    }

    protected function cleanSlug(?string $value, int $maxLength = 60): string
    {
        $value = strtolower($this->cleanText($value, $maxLength));
        $value = preg_replace('/[^a-z0-9_-]+/', '', $value) ?? '';

        return mb_substr($value, 0, $maxLength);
    }

    protected function cleanDigits(?string $value, int $maxLength = 20): string
    {
        $value = preg_replace('/\D+/', '', (string) $value) ?? '';

        return substr($value, 0, $maxLength);
    }
}
