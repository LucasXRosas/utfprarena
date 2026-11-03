<?php

namespace Tests\Support\Upload;

use App\Services\FileStorage;
use Core\Constants\Constants;

/**
 * Gera arquivos temporários e simula entradas de $_FILES para os testes de upload.
 */
class UploadFixtures
{
    /** @var array<int, string> */
    private static array $tmpFiles = [];

    /**
     * Simula a entrada de $_FILES['campo'] para um arquivo temporário.
     *
     * @return array<string, mixed>
     */
    public static function fileEntry(string $tmpPath, string $originalName, int $error = UPLOAD_ERR_OK): array
    {
        return [
            'name' => $originalName,
            'type' => 'image/jpeg', // valor enviado pelo navegador: NÃO é confiável e é ignorado pelo validador
            'tmp_name' => $tmpPath,
            'error' => $error,
            'size' => is_file($tmpPath) ? (int) filesize($tmpPath) : 0,
        ];
    }

    /**
     * PNG real e válido, montado byte a byte (assinatura + chunks IHDR/IDAT/IEND), sem depender da extensão GD.
     */
    public static function pngContent(int $width = 4, int $height = 4): string
    {
        $raw = '';
        for ($y = 0; $y < $height; $y++) {
            $raw .= "\0" . str_repeat("\xFF\x80\x00", $width);
        }

        $chunk = static function (string $type, string $data): string {
            return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        };

        return "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            . $chunk('IDAT', (string) gzcompress($raw))
            . $chunk('IEND', '');
    }

    public static function createFile(string $content): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, $content);
        self::$tmpFiles[] = $path;

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    public static function validPng(string $name = 'quadra.png'): array
    {
        return self::fileEntry(self::createFile(self::pngContent()), $name);
    }

    /**
     * @return array<string, mixed>
     */
    public static function validJpeg(string $name = 'quadra.jpg'): array
    {
        $content = (string) file_get_contents(Constants::rootPath()->join('tests/files/avatar_test.jpg'));
        return self::fileEntry(self::createFile($content), $name);
    }

    /**
     * Arquivo maior que o limite permitido (2MB).
     *
     * @return array<string, mixed>
     */
    public static function oversized(string $name = 'grande.png', int $bytes = 3 * 1024 * 1024): array
    {
        return self::fileEntry(self::createFile(self::pngContent() . str_repeat("\0", $bytes)), $name);
    }

    /**
     * Script PHP renomeado para parecer uma imagem.
     *
     * @return array<string, mixed>
     */
    public static function phpScript(string $name = 'shell.php'): array
    {
        return self::fileEntry(self::createFile("<?php echo 'codigo malicioso'; ?>\n"), $name);
    }

    /**
     * Arquivo poliglota: é um PNG válido, mas carrega código PHP embutido no final.
     *
     * @return array<string, mixed>
     */
    public static function polyglotPng(string $name = 'poliglota.png'): array
    {
        return self::fileEntry(self::createFile(self::pngContent() . "<?php echo 'codigo embutido'; ?>"), $name);
    }

    /**
     * GIF válido (formato não aceito pela aplicação).
     *
     * @return array<string, mixed>
     */
    public static function gif(string $name = 'animacao.gif'): array
    {
        $gif = (string) base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
        return self::fileEntry(self::createFile($gif), $name);
    }

    public static function cleanup(): void
    {
        foreach (self::$tmpFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        self::$tmpFiles = [];

        // Remove os uploads gerados pelos testes (pasta isolada uploads/test).
        // Proteção: nunca apaga a pasta de uploads de desenvolvimento.
        if (!str_ends_with(Constants::uploadsUrl(), '/test')) {
            return;
        }

        $storage = new FileStorage();
        $storage->deleteDirectory('courts');
        $storage->deleteDirectory('users');
    }
}
