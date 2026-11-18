<?php

declare(strict_types=1);

final class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'No tenés permiso para realizar esta acción', array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, $details, $previous);
    }
}
