<?php

declare(strict_types=1);

final class PdoErrorTranslator
{
    public function translate(PDOException $e): Throwable
    {
        $state = (string) ($e->errorInfo[0] ?? '');
        $code = (int) ($e->errorInfo[1] ?? 0);
        $msg = (string) ($e->errorInfo[2] ?? '');

        if ($state === '45000') {
            if (str_starts_with($msg, 'Sin permiso')) {
                return new ForbiddenException($msg, [], $e);
            }

            if (str_contains($msg, 'no existe')) {
                return new NotFoundException('El aviso no existe', [], $e);
            }

            return new BusinessRuleException($msg, [], $e);
        }

        if (in_array($code, [1142, 1370], true)) {
            return new ForbiddenException('Sin permiso para realizar esta acción', [], $e);
        }

        if (in_array($code, [4025, 3819, 1264, 1406, 1452, 1048], true)) {
            return new BusinessRuleException('Algún dato no cumple las reglas del sistema.', [], $e);
        }
        error_log('[avisos] ' . $e->getMessage());

        return new \RuntimeException('Error de base de datos', 0, $e);
    }
}
