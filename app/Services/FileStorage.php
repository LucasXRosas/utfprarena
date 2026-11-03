<?php

namespace App\Services;

use Core\Constants\Constants;

/**
 * Responsável por gravar arquivos enviados no filesystem e removê-los.
 * Todos os caminhos recebidos são RELATIVOS à pasta de uploads
 * (ex: "courts/1/court_ab12cd...png").
 */
class FileStorage
{
    private string $basePath;
    private string $baseUrl;

    public function __construct(?string $basePath = null, ?string $baseUrl = null)
    {
        $this->basePath = rtrim($basePath ?? (string) Constants::uploadsPath(), '/');
        $this->baseUrl = rtrim($baseUrl ?? Constants::uploadsUrl(), '/');
    }

    /**
     * Move o arquivo temporário do upload para o diretório definitivo.
     *
     * @param string $tmpPath     caminho temporário ($_FILES['campo']['tmp_name'])
     * @param string $relativeDir pasta de destino relativa (ex: "courts/1")
     * @param string $fileName    nome final do arquivo (gerado pelo sistema)
     * @return string caminho relativo do arquivo gravado
     */
    public function store(string $tmpPath, string $relativeDir, string $fileName): string
    {
        $relativeDir = trim($relativeDir, '/');
        $directory = $this->basePath . '/' . $relativeDir;

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $relativePath = $relativeDir . '/' . basename($fileName);

        if (!$this->moveUploadedFile($tmpPath, $this->absolutePath($relativePath))) {
            throw new \RuntimeException('Não foi possível gravar o arquivo no servidor.');
        }

        return $relativePath;
    }

    public function delete(string $relativePath): bool
    {
        $path = $this->absolutePath($relativePath);

        if (is_file($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Remove uma pasta de uploads com todo o seu conteúdo (ex: galeria inteira de uma quadra).
     */
    public function deleteDirectory(string $relativeDir): void
    {
        $relativeDir = trim($relativeDir, '/');
        $directory = $this->absolutePath($relativeDir);
        if ($relativeDir === '' || !is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (is_dir($directory . '/' . $item)) {
                $this->deleteDirectory($relativeDir . '/' . $item);
            } else {
                unlink($directory . '/' . $item);
            }
        }

        rmdir($directory);
    }

    public function exists(string $relativePath): bool
    {
        return is_file($this->absolutePath($relativePath));
    }

    public function absolutePath(string $relativePath): string
    {
        return $this->basePath . '/' . ltrim($relativePath, '/');
    }

    public function url(string $relativePath): string
    {
        return $this->baseUrl . '/' . ltrim($relativePath, '/');
    }

    /**
     * Gera um nome aleatório e imprevisível (nunca reaproveita o nome enviado pelo usuário).
     */
    public static function randomName(string $prefix, string $extension): string
    {
        return $prefix . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
    }

    /**
     * move_uploaded_file() só aceita arquivos que chegaram por HTTP POST,
     * impedindo que um caminho arbitrário do servidor seja "movido".
     * Isolado em um método protegido para que os testes possam substituí-lo.
     */
    protected function moveUploadedFile(string $from, string $to): bool
    {
        return move_uploaded_file($from, $to);
    }
}
