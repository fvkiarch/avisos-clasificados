<?php

declare(strict_types=1);

final class AuthUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $role,
        public readonly array $permissions,
    ) {
    }

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'permissions' => $this->permissions,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['id'],
            (string) $data['name'],
            (string) $data['role'],
            array_values((array) $data['permissions']),
        );
    }
}
