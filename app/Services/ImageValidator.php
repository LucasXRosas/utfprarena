<?php

namespace App\Services;

/**
 * Valida um arquivo enviado via formulário multipart/form-data ($_FILES['campo']).
 *
 * Regras aplicadas (na ordem):
 *  1. O upload precisa ter chegado sem erro (UPLOAD_ERR_OK);
 *  2. Arquivo não vazio e dentro do tamanho máximo (padrão 2MB);
 *  3. O nome não pode conter extensões executáveis em nenhuma parte (ex: shell.php.jpg);
 *  4. A extensão final precisa estar entre os formatos aceitos (JPEG, PNG, WEBP);
 *  5. O MIME type REAL é detectado pelo conteúdo (magic bytes via finfo),
 *     ignorando o "type" enviado pelo navegador (que pode ser forjado);
 *  6. A extensão precisa ser coerente com o MIME detectado;
 *  7. getimagesize() precisa conseguir ler a estrutura da imagem;
 *  8. O conteúdo não pode conter código embutido (<?php, <script>), barrando arquivos poliglotas.
 */
class ImageValidator
{
    public const MAX_SIZE = 2 * 1024 * 1024; // 2MB

    /** @var array<string, array<int, string>> MIME type => extensões aceitas */
    public const ALLOWED_TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png'  => ['png'],
        'image/webp' => ['webp'],
    ];

    /** @var array<int, string> */
    private const FORBIDDEN_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps', 'pht',
        'sh', 'bash', 'exe', 'bat', 'cmd', 'com', 'msi', 'js', 'mjs', 'html', 'htm',
        'svg', 'pl', 'py', 'cgi', 'asp', 'aspx', 'jsp', 'htaccess',
    ];

    /** @var array<int, string> */
    private const FORBIDDEN_CONTENT = ['<?php', '<script', '__halt_compiler'];

    /** @var array<int, string> */
    private array $errors = [];

    private ?string $mimeType = null;

    /**
     * @param array<string, array<int, string>> $allowedTypes
     */
    public function __construct(
        private int $maxSize = self::MAX_SIZE,
        private array $allowedTypes = self::ALLOWED_TYPES
    ) {
    }

    /**
     * @param array<string, mixed>|null $file Entrada de $_FILES (name, type, tmp_name, error, size)
     */
    public function validate(?array $file): bool
    {
        $this->errors = [];
        $this->mimeType = null;

        // 1. Upload precisa ter chegado corretamente
        if ($file === null || !isset($file['error'], $file['tmp_name'], $file['name'])) {
            return $this->fail('Nenhum arquivo foi enviado.');
        }

        $error = (int) $file['error'];
        if ($error === UPLOAD_ERR_NO_FILE) {
            return $this->fail('Nenhum arquivo foi enviado.');
        }

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return $this->fail('O arquivo excede o tamanho máximo permitido de ' . $this->maxSizeLabel() . '.');
        }

        if ($error !== UPLOAD_ERR_OK) {
            return $this->fail('Falha no envio do arquivo (código ' . $error . ').');
        }

        $tmpPath = (string) $file['tmp_name'];
        if ($tmpPath === '' || !is_file($tmpPath)) {
            return $this->fail('Arquivo temporário do upload não encontrado.');
        }

        // 2. Tamanho: usa o tamanho real em disco, não o valor informado pelo cliente
        $size = (int) filesize($tmpPath);
        if ($size === 0) {
            return $this->fail('O arquivo enviado está vazio.');
        }

        if ($size > $this->maxSize) {
            return $this->fail('O arquivo excede o tamanho máximo permitido de ' . $this->maxSizeLabel() . '.');
        }

        // 3. Extensões executáveis em qualquer segmento do nome (shell.php.jpg, foto.phtml...)
        $originalName = strtolower(basename((string) $file['name']));
        $segments = explode('.', $originalName);
        array_shift($segments);
        foreach ($segments as $segment) {
            if (in_array($segment, self::FORBIDDEN_EXTENSIONS, true)) {
                return $this->fail('Arquivo malicioso: extensões executáveis (.' . $segment . ') não são permitidas.');
            }
        }

        // 4. Extensão final precisa ser de uma imagem aceita
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        if (!in_array($extension, $this->allowedExtensions(), true)) {
            return $this->fail('Formato não permitido. Envie apenas: ' . $this->allowedLabel() . '.');
        }

        // 5. MIME type real, detectado pelos magic bytes do conteúdo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = (string) $finfo->file($tmpPath);
        if (!array_key_exists($detected, $this->allowedTypes)) {
            return $this->fail(
                'Arquivo malicioso ou inválido: o conteúdo real (' . $detected . ') não é uma imagem permitida.'
            );
        }

        // 6. Extensão coerente com o conteúdo real
        if (!in_array($extension, $this->allowedTypes[$detected], true)) {
            return $this->fail(
                'A extensão .' . $extension . ' não corresponde ao conteúdo do arquivo (' . $detected . ').'
            );
        }

        // 7. Estrutura da imagem precisa ser legível
        $imageInfo = @getimagesize($tmpPath);
        if ($imageInfo === false || $imageInfo[0] < 1 || $imageInfo[1] < 1) {
            return $this->fail('O arquivo está corrompido ou não é uma imagem válida.');
        }

        // 8. Conteúdo não pode carregar código embutido (arquivo poliglota imagem + script)
        $content = (string) file_get_contents($tmpPath);
        foreach (self::FORBIDDEN_CONTENT as $signature) {
            if (stripos($content, $signature) !== false) {
                return $this->fail('Arquivo malicioso: foi detectado código embutido na imagem.');
            }
        }

        $this->mimeType = $detected;
        return true;
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * MIME type detectado pelo conteúdo (disponível após validate() retornar true).
     */
    public function mimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * Extensão segura derivada do MIME detectado (nunca do nome enviado pelo usuário).
     */
    public function safeExtension(): ?string
    {
        if ($this->mimeType === null) {
            return null;
        }

        return $this->allowedTypes[$this->mimeType][0];
    }

    public function maxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * Valor para o atributo "accept" do <input type="file">.
     */
    public function acceptAttribute(): string
    {
        return implode(',', array_keys($this->allowedTypes));
    }

    public function maxSizeLabel(): string
    {
        return round($this->maxSize / 1024 / 1024, 1) . 'MB';
    }

    public function allowedLabel(): string
    {
        return strtoupper(implode(', ', $this->allowedExtensions()));
    }

    /**
     * @return array<int, string>
     */
    private function allowedExtensions(): array
    {
        return array_merge(...array_values($this->allowedTypes));
    }

    private function fail(string $message): bool
    {
        $this->errors[] = $message;
        return false;
    }
}
