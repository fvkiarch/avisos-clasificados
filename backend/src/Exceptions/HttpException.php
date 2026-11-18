<?php

declare(strict_types=1);

class HttpException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $status = 500,
        private readonly array $details = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

}
