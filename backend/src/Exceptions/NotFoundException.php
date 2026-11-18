<?php

declare(strict_types=1);

final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Recurso no encontrado', array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, $details, $previous);
    }
}
