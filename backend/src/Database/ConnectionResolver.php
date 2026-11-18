<?php

declare(strict_types=1);

final class ConnectionResolver
{
    public function __construct(private readonly ConnectionFactory $factory, private readonly CurrentUser $user)
    {
    }

    public function current(): PDO
    {
        return $this->factory->get($this->user->profile());
    }

    public function as(string $profile): PDO
    {
        return $this->factory->get($profile);
    }
}
