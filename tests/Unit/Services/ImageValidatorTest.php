<?php

namespace Tests\Unit\Services;

use App\Services\ImageValidator;
use PHPUnit\Framework\TestCase;
use Tests\Support\Upload\UploadFixtures;

/**
 * Testes das validações de upload (tamanho, formato e tratamento de upload malicioso).
 * Não acessa banco de dados.
 */
class ImageValidatorTest extends TestCase
{
    private ImageValidator $validator;

    public function setUp(): void
    {
        $this->validator = new ImageValidator();
    }

    public function tearDown(): void
    {
        UploadFixtures::cleanup();
    }

    /* ---------------- Formatos aceitos ---------------- */

    public function test_accepts_valid_png(): void
    {
        $this->assertTrue($this->validator->validate(UploadFixtures::validPng()));
        $this->assertEquals('image/png', $this->validator->mimeType());
        $this->assertEquals('png', $this->validator->safeExtension());
        $this->assertEmpty($this->validator->errors());
    }

    public function test_accepts_valid_jpeg_with_uppercase_extension(): void
    {
        $this->assertTrue($this->validator->validate(UploadFixtures::validJpeg('FOTO.JPEG')));
        $this->assertEquals('image/jpeg', $this->validator->mimeType());
        $this->assertEquals('jpg', $this->validator->safeExtension());
    }

    public function test_rejects_format_not_allowed(): void
    {
        $this->assertFalse($this->validator->validate(UploadFixtures::gif()));
        $this->assertStringContainsString('Formato não permitido', (string) $this->validator->firstError());
    }

    public function test_rejects_extension_that_does_not_match_content(): void
    {
        // Conteúdo PNG com extensão .jpg
        $this->assertFalse($this->validator->validate(UploadFixtures::validPng('foto.jpg')));
        $this->assertStringContainsString('não corresponde ao conteúdo', (string) $this->validator->firstError());
    }

    /* ---------------- Tamanho do arquivo ---------------- */

    public function test_rejects_file_bigger_than_2mb(): void
    {
        $this->assertFalse($this->validator->validate(UploadFixtures::oversized()));
        $this->assertStringContainsString('tamanho máximo permitido de 2MB', (string) $this->validator->firstError());
    }

    public function test_rejects_upload_error_ini_size(): void
    {
        $file = UploadFixtures::fileEntry('', 'enorme.png', UPLOAD_ERR_INI_SIZE);
        $this->assertFalse($this->validator->validate($file));
        $this->assertStringContainsString('tamanho máximo', (string) $this->validator->firstError());
    }

    public function test_custom_max_size(): void
    {
        $validator = new ImageValidator(maxSize: 10);
        $this->assertFalse($validator->validate(UploadFixtures::validPng()));
        $this->assertEquals(10, $validator->maxSize());
    }

    public function test_rejects_empty_file(): void
    {
        $file = UploadFixtures::fileEntry(UploadFixtures::createFile(''), 'vazio.png');
        $this->assertFalse($this->validator->validate($file));
        $this->assertEquals('O arquivo enviado está vazio.', $this->validator->firstError());
    }

    public function test_rejects_missing_file(): void
    {
        $this->assertFalse($this->validator->validate(null));
        $this->assertEquals('Nenhum arquivo foi enviado.', $this->validator->firstError());

        $this->assertFalse($this->validator->validate(UploadFixtures::fileEntry('', '', UPLOAD_ERR_NO_FILE)));
        $this->assertEquals('Nenhum arquivo foi enviado.', $this->validator->firstError());
    }

    /* ---------------- Upload malicioso ---------------- */

    public function test_rejects_php_script(): void
    {
        $this->assertFalse($this->validator->validate(UploadFixtures::phpScript('shell.php')));
        $this->assertStringContainsString('Arquivo malicioso', (string) $this->validator->firstError());
    }

    public function test_rejects_double_extension(): void
    {
        // Mesmo sendo um PNG válido, o nome esconde uma extensão executável
        $this->assertFalse($this->validator->validate(UploadFixtures::validPng('shell.php.png')));
        $this->assertStringContainsString('(.php)', (string) $this->validator->firstError());
    }

    public function test_rejects_script_renamed_to_jpg(): void
    {
        // Extensão .jpg e "type" image/jpeg forjados, mas o conteúdo real é texto PHP
        $this->assertFalse($this->validator->validate(UploadFixtures::phpScript('foto.jpg')));
        $this->assertStringContainsString('conteúdo real', (string) $this->validator->firstError());
    }

    public function test_rejects_polyglot_image_with_embedded_code(): void
    {
        $this->assertFalse($this->validator->validate(UploadFixtures::polyglotPng()));
        $this->assertStringContainsString('código embutido', (string) $this->validator->firstError());
    }

    public function test_helpers_for_views(): void
    {
        $this->assertEquals('image/jpeg,image/png,image/webp', $this->validator->acceptAttribute());
        $this->assertEquals('JPG, JPEG, PNG, WEBP', $this->validator->allowedLabel());
        $this->assertEquals('2MB', $this->validator->maxSizeLabel());
    }
}
