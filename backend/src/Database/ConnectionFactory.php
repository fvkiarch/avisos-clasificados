<?php

declare(strict_types=1);

final class ConnectionFactory
{
    private array $pool = [];

    public function __construct(private readonly Config $config)
    {
    }

    public function get(string $profile): PDO
    {
        if (isset($this->pool[$profile])) {
            return $this->pool[$profile];
        }
        $cred = $this->config->get("db.profiles.$profile");

        if (!is_array($cred)) {
            throw new InvalidArgumentException("Perfil inválido de la base de datos: $profile");
        }
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $this->config->get('db.host'), $this->config->get('db.name'));

        return $this->pool[$profile] = new PDO($dsn, $cred['user'], $cred['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'",
        ]);
    }
}
