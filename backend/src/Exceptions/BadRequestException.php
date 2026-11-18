<?php

declare(strict_types=1);

final class BadRequestException extends HttpException
{
    public function __construct(string $message = 'Solicitud inválida', array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 400, $details, $previous);
    }
}
