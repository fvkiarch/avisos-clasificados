<?php

declare(strict_types=1);


spl_autoload_register(static function (string $class): void {
    static $map = null;

    if ($map === null) {
        $map = [];
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $map[$file->getBasename('.php')] = $file->getPathname();
            }
        }
    }

    if (isset($map[$class])) {
        require_once $map[$class];
    }
});
