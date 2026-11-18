<?php

declare(strict_types=1);

final class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Autenticación requerida', array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 401, $details, $previous);
    }
}
