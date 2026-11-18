<?php

declare(strict_types=1);

final class BusinessRuleException extends HttpException
{
    public function __construct(string $message = 'La operación viola una regla del sistema', array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $details, $previous);
    }
}
