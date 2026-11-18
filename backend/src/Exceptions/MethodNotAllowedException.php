<?php

declare(strict_types=1);

final class MethodNotAllowedException extends HttpException
{
    public function __construct(string $message = 'Método no permitido', array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 405, $details, $previous);
    }


}
