<?php

declare(strict_types=1);

final class ValidationException extends HttpException
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Datos inválidos', 422, ['fields' => $errors]);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
