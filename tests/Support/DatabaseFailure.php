<?php

namespace Tests\Support;

use Core\Database\Database;

/**
 * Simula uma falha real de persistência: cria um trigger que faz o MySQL rejeitar
 * a operação (SIGNAL SQLSTATE '45000'), o que chega na aplicação como PDOException.
 *
 * O trigger some junto com o banco de teste, que é recriado a cada teste (Tests\TestCase).
 */
class DatabaseFailure
{
    /**
     * @param string $operation UPDATE ou DELETE
     */
    public static function on(string $table, string $operation): void
    {
        $operation = strtoupper($operation);
        $trigger = 'fail_' . strtolower($operation) . '_' . $table;

        Database::getDatabaseConn()->exec(
            "CREATE TRIGGER {$trigger} BEFORE {$operation} ON {$table} FOR EACH ROW "
            . "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'falha simulada de persistencia'"
        );
    }
}
