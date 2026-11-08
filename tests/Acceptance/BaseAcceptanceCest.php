<?php

namespace Tests\Acceptance;

use App\Services\FileStorage;
use Core\Constants\Constants;
use Core\Database\Database;
use Core\Env\EnvLoader;
use Database\Populate\Seeder;
use Tests\Support\AcceptanceTester;

/**
 * Base de todos os testes de aceitação.
 *
 * O navegador (Selenium) acessa http://web:8080, servido pelo container php_test
 * (APP_ENV=testing, banco arena_test, uploads em public/assets/uploads/test).
 * Antes de CADA cenário o banco de teste é recriado do zero e populado pelo Seeder,
 * então o resultado não depende da ordem dos testes, de execuções anteriores nem
 * do uso manual do sistema — e o banco de desenvolvimento nunca é alterado.
 */
class BaseAcceptanceCest
{
    public function _before(AcceptanceTester $page): void
    {
        EnvLoader::init();

        // Trava de segurança: este método APAGA o banco — só pode rodar no ambiente de testes
        if (($_ENV['APP_ENV'] ?? '') !== 'testing' || !str_ends_with((string) ($_ENV['DB_DATABASE'] ?? ''), '_test')) {
            throw new \RuntimeException(
                'Testes de aceitação devem rodar com APP_ENV=testing e um banco *_test (use ./run test:browser).'
            );
        }

        $this->waitForDatabase();

        Database::drop();
        Database::create();
        Database::migrate();
        Seeder::run();

        $this->resetTestUploads();
    }

    public function _after(AcceptanceTester $page): void
    {
        $this->resetTestUploads();
        Database::drop();
    }

    /**
     * Logo após "./run up -d", o MySQL ainda pode estar inicializando o volume (Connection refused).
     * Aguarda até 90s pela conexão; com o banco pronto, retorna na primeira tentativa.
     */
    private function waitForDatabase(int $timeoutSeconds = 90): void
    {
        $deadline = time() + $timeoutSeconds;

        while (true) {
            try {
                Database::getConn();
                return;
            } catch (\PDOException $e) {
                if (time() >= $deadline) {
                    throw $e;
                }
                sleep(2);
            }
        }
    }

    /**
     * Esvazia public/assets/uploads/test e garante que o PHP-FPM (www-data) consiga gravar nela,
     * já que o Codeception roda como root e poderia criar a pasta sem permissão de escrita.
     */
    private function resetTestUploads(): void
    {
        $storage = new FileStorage();
        $storage->deleteDirectory('courts');
        $storage->deleteDirectory('users');

        $directory = (string) Constants::uploadsPath();
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        @chmod($directory, 0777);
    }
}
