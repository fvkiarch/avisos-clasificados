<?php

declare(strict_types=1);


$host     = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbname   = 'avisos_clasificados';

$mysql = getenv('MYSQL_BIN') ?: '/opt/lampp/bin/mysql';


$sqlFiles = [
    __DIR__ . '/../database/sql/01_ddl.sql',
    __DIR__ . '/../database/sql/02_dml.sql',
    __DIR__ . '/../database/sql/05_seguridad_rbac.sql',
    __DIR__ . '/../database/sql/06_backups.sql',
];


function ejecutarSql(
    string $file,
    string $mysql,
    string $host,
    string $username,
    string $password,
    string $dbname
): void {

    $command = [
        $mysql,
        '--default-character-set=utf8mb4',
        "--host=$host",
        "--user=$username",
        $dbname
    ];

    $environment = getenv();

    $environment = $_ENV;

    if ($password !== '') {
        $environment['MYSQL_PWD'] = $password;
    } else {
        unset($environment['MYSQL_PWD']);
    }

    $descriptors = [
        0 => ['file', $file, 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open(
        $command,
        $descriptors,
        $pipes,
        null,
        $environment
    );

    if (!is_resource($process)) {
        throw new RuntimeException('No se pudo iniciar MariaDB.');
    }

    $output = stream_get_contents($pipes[1]);
    $error  = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        throw new RuntimeException(
            "Error ejecutando " . basename($file) . ":\n" . $error
        );
    }
}


try {

    echo "[1/2] Creando/verificando base de datos...\n";

    if (!is_executable($mysql)) {
        throw new RuntimeException(
            "No se encontró el cliente MariaDB: $mysql"
        );
    }

    $pdo = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    $pdo->exec("
        CREATE DATABASE IF NOT EXISTS `$dbname`
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci
    ");

    echo "[OK] Base de datos '$dbname' creada/verificada.\n";


    echo "\n[2/2] Ejecutando scripts SQL...\n";

    foreach ($sqlFiles as $file) {

        if (!is_file($file)) {
            throw new RuntimeException(
                "No se encontró el archivo: $file"
            );
        }

        echo "[EJECUTANDO] " . basename($file) . "\n";

        ejecutarSql(
            $file,
            $mysql,
            $host,
            $username,
            $password,
            $dbname
        );

        echo "[OK] " . basename($file) . "\n";
    }

    echo "\n[OK] Inicialización completada correctamente.\n";

} catch (Throwable $e) {

    echo "\n[ERROR] Falló la inicialización:\n";
    echo $e->getMessage() . "\n";

    exit(1);
}
