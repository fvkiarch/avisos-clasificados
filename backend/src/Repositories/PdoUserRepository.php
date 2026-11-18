<?php

declare(strict_types=1);

final class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly ConnectionResolver $db,
        private readonly PdoErrorTranslator $errors,
    ) {
    }

    public function loginData(string $email): ?array
    {
        $statement = $this->db->as('visitante')->prepare('CALL sp_datos_login(?)');
        $statement->execute([$email]);
        $row = $statement->fetch();
        $statement->closeCursor();

        return $row ?: null;
    }

    public function permissions(int $authorId): array
    {
        $statement = $this->db->as('visitante')->prepare('CALL sp_permisos_autor(?)');
        $statement->execute([$authorId]);
        $rows = $statement->fetchAll(PDO::FETCH_COLUMN);
        $statement->closeCursor();

        return $rows;
    }

    public function all(): array
    {
        try {
            $statement = $this->db->current()->prepare('CALL sp_listar_autores()');
            $statement->execute();
            $rows = $statement->fetchAll();
            $statement->closeCursor();
            return $rows;
        } catch (PDOException $e) {
            throw $this->errors->translate($e);
        }
    }

    public function create(string $name, string $email, string $passwordHash, string $role): int
    {
        try {
            $pdo = $this->db->current();
            $statement = $pdo->prepare('CALL sp_crear_autor(?, ?, ?, ?, @id)');
            $statement->execute([$name, $email, $passwordHash, $role]);
            $statement->closeCursor();
            return (int) $pdo->query('SELECT @id')->fetchColumn();
        } catch (PDOException $e) {
            throw $this->errors->translate($e);
        }
    }

    public function delete(int $id): void
    {
        try {
            $statement = $this->db->current()->prepare('CALL sp_eliminar_autor(?)');
            $statement->execute([$id]);
            $statement->closeCursor();
        } catch (PDOException $e) {
            throw $this->errors->translate($e);
        }
    }
}