<?php

declare(strict_types=1);

final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly CurrentUser $current,
    ) {
    }

    public function listAll(): array
    {
        if (!$this->current->can(Permission::LISTING_DELETE_ANY)) {
            throw new ForbiddenException('Sin permisos de administrador');
        }

        return $this->users->all();
    }

    public function create(array $input): int
    {
        if (!$this->current->can(Permission::LISTING_DELETE_ANY)) {
            throw new ForbiddenException('Sin permisos de administrador');
        }

        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $role = trim((string) ($input['role'] ?? 'visitante'));

        $errors = [];
        if ($name === '') $errors['name'] = 'El nombre es obligatorio.';
        if ($email === '') $errors['email'] = 'El email es obligatorio.';
        if ($password === '') $errors['password'] = 'La contraseña es obligatoria.';

        if ($errors) {
            throw new ValidationException($errors);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        return $this->users->create($name, $email, $hash, $role);
    }

    public function delete(int $id): void
    {
        if (!$this->current->can(Permission::LISTING_DELETE_ANY)) {
            throw new ForbiddenException('Sin permisos de administrador');
        }

        if ($this->current->id() === $id) {
            throw new BusinessRuleException('No puedes eliminar tu propia cuenta.');
        }

        $this->users->delete($id);
    }
}