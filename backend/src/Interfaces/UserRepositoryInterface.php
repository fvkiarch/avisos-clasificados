<?php

declare(strict_types=1);

interface UserRepositoryInterface
{
	public function loginData(string $email): ?array;
    public function permissions(int $authorId): array;
    public function all(): array;
    public function create(string $name, string $email, string $passwordHash, string $role): int;
    public function delete(int $id): void;
}
