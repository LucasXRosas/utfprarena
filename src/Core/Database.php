<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Gerenciador de conexao com Banco de Dados PostgreSQL via PDO.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'db';
            $port = getenv('DB_PORT') ?: '5432';
            $dbName = getenv('DB_DATABASE') ?: 'utfprarena';
            $user = getenv('DB_USERNAME') ?: 'utfpr';
            $password = getenv('DB_PASSWORD') ?: 'arena_secret';

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbName};";

            try {
                self::$instance = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // Em ambiente de teste ou inicializacao sem banco ativo, lanca excecao controlada
                throw new \RuntimeException("Falha na conexao com banco de dados: " . $e->getMessage(), 500, $e);
            }
        }

        return self::$instance;
    }

    public static function setMockConnection(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}
