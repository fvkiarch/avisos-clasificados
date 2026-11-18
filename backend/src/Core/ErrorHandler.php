<?php

declare(strict_types=1);

final class ErrorHandler
{
    public function __construct(private readonly Config $config)
    {
    }

    public function render(Throwable $e): Response
    {
        $http = $e instanceof HttpException;
        $status = $http ? $e->getStatus() : 500;
        $error = ['status' => $status, 'message' => $http ? $e->getMessage() : 'Error interno del servidor']
               + ($http ? $e->getDetails() : []);

        if (!$http) {
            error_log('[avisos] ' . $e::class . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());

            if ($this->config->get('debug')) {
                $error['debug'] = $e->getMessage();
            }
        }

        return Response::json(['error' => $error], $status, $status === 401 ? ['WWW-Authenticate' => 'Bearer'] : []);
    }
}
