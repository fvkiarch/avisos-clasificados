<?php

declare(strict_types=1);

final class CurrentUser
{
    private ?AuthUser $user = null;

    public function set(?AuthUser $user): void
    {
        $this->user = $user;
    }

    public function user(): ?AuthUser
    {
        return $this->user;
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function id(): ?int
    {
        return $this->user?->id;
    }

    public function can(string $permission): bool
    {
        return $this->user?->can($permission) ?? false;
    }

    public function profile(): string
    {
        return match ($this->user?->role) {
            Role::ADMIN => 'admin',
            Role::PUBLISHER => 'publicador',
            default => 'visitante',
        };
    }
}
