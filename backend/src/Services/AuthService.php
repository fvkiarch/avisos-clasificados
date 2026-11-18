<?php

declare(strict_types=1);

final class AuthService
{

    private const DUMMY_HASH = '$2y$10$ea0zZDG9qqkq43wUIoEFQ.cjMwLj4Az/PnWaRbKdqIovd/GEUmO0S';

    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    public function attempt(string $email, string $password): ?AuthUser
    {
        $row = $this->users->loginData(trim($email));
        $valid = password_verify($password, $row['password_hash'] ?? self::DUMMY_HASH);

        if (!$row || !$valid || $row['rol'] === Role::BACKUP) {
            return null;
        }

        return new AuthUser(
            (int) $row['id_autor'],
            $row['nombre'],
            $row['rol'],
            $this->users->permissions((int) $row['id_autor']),
        );
    }
}
