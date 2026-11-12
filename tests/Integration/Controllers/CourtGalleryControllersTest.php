<?php

namespace Tests\Integration\Controllers;

use App\Models\Court;
use App\Models\CourtImage;
use App\Models\User;
use Lib\Authentication\Auth;
use Tests\Support\DatabaseFailure;
use Tests\Support\Upload\TestFileStorage;
use Tests\Support\Upload\UploadFixtures;

/**
 * Testes de controller da galeria de quadras (Admin\CourtsController, Admin\CourtImagesController e CourtsController).
 */
class CourtGalleryControllersTest extends ControllerTestCase
{
    private Court $court;

    public function setUp(): void
    {
        parent::setUp();
        unset($_SESSION['flash']);

        $admin = new User([
            'name' => 'Admin Galeria',
            'email' => 'admin.galeria@arena.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => User::ROLE_MANAGER,
        ]);
        $admin->save();
        Auth::login($admin);

        $this->court = new Court(['name' => 'Quadra Controller Test']);
        $this->court->save();
    }

    public function tearDown(): void
    {
        unset($_SESSION['flash']);
        UploadFixtures::cleanup();
        parent::tearDown();
    }

    private function createImageWithFile(): CourtImage
    {
        $storage = new TestFileStorage();
        $file = UploadFixtures::validPng();
        $storage->store($file['tmp_name'], 'courts/' . $this->court->id, 'court_test.png');

        $image = new CourtImage([
            'court_id' => $this->court->id,
            'file_name' => 'court_test.png',
            'original_name' => 'original.png',
            'mime_type' => 'image/png',
            'size' => 100,
            'caption' => 'Legenda de Teste',
        ]);
        $image->save();

        return $image;
    }

    public function test_admin_index_lists_courts_with_image_count(): void
    {
        $this->createImageWithFile();

        $response = $this->get('index', 'App\Controllers\Admin\CourtsController');

        $this->assertMatchesRegularExpression('/Quadras e Galerias de Fotos/', $response);
        $this->assertMatchesRegularExpression('/Quadra Controller Test/', $response);
        $this->assertMatchesRegularExpression('/1 foto\(s\)/', $response);
    }

    public function test_admin_show_renders_gallery_with_multipart_form(): void
    {
        $image = $this->createImageWithFile();

        $response = $this->get('show', 'App\Controllers\Admin\CourtsController', ['id' => $this->court->id]);

        $this->assertStringContainsString('enctype="multipart/form-data"', $response);
        $this->assertStringContainsString('Legenda de Teste', $response);
        $this->assertStringContainsString($image->url(), $response);
        $this->assertStringContainsString('arquivo presente', $response);
    }

    public function test_admin_create_court(): void
    {
        $response = $this->post('create', 'App\Controllers\Admin\CourtsController', [
            'court' => ['name' => 'Quadra Nova', 'status' => Court::STATUS_MAINTENANCE],
        ]);

        $created = Court::findBy(['name' => 'Quadra Nova']);
        $this->assertNotNull($created);
        $this->assertTrue($created->isMaintenance());
        $this->assertStringContainsString('Location: /admin/courts/' . $created->id, $response);
    }

    public function test_admin_destroy_court_removes_images_from_db_and_filesystem(): void
    {
        $image = $this->createImageWithFile();
        $path = (new TestFileStorage())->absolutePath($image->relativePath());
        $this->assertFileExists($path);

        $response = $this->post('destroy', 'App\Controllers\Admin\CourtsController', ['id' => $this->court->id]);

        $this->assertStringContainsString('Location: /admin/courts', $response);
        $this->assertNull(Court::findById($this->court->id));
        $this->assertNull(CourtImage::findById($image->id));
        $this->assertFileDoesNotExist($path);
        $this->assertStringContainsString('1 imagem(ns) apagada(s)', $_SESSION['flash']['success'] ?? '');
    }

    public function test_upload_image_to_nonexistent_gallery_is_rejected(): void
    {
        $response = $this->post('create', 'App\Controllers\Admin\CourtImagesController', ['court_id' => 9999]);

        $this->assertStringContainsString('Location: /admin/courts', $response);
        $this->assertStringContainsString('Galeria inexistente', $_SESSION['flash']['danger'] ?? '');
        $this->assertCount(0, CourtImage::all());
    }

    public function test_upload_without_file_shows_error(): void
    {
        $response = $this->post('create', 'App\Controllers\Admin\CourtImagesController', [
            'court_id' => $this->court->id,
        ]);

        // Não redireciona: o erro aparece embaixo do campo de arquivo
        $this->assertStringNotContainsString('Location:', $response);
        $this->assertStringContainsString('id="error_image">Nenhum arquivo foi enviado.', $response);
        $this->assertStringContainsString('id="court_image_form" novalidate', $response);
    }

    public function test_upload_error_keeps_typed_caption(): void
    {
        $response = $this->post('create', 'App\Controllers\Admin\CourtImagesController', [
            'court_id' => $this->court->id,
            'caption' => 'Legenda digitada',
        ], ['image' => UploadFixtures::phpScript('foto.php.jpg')]);

        $this->assertMatchesRegularExpression('/id="error_image">Arquivo malicioso/', $response);
        $this->assertStringContainsString('value="Legenda digitada"', $response);
    }

    public function test_admin_create_court_with_empty_name_shows_field_error(): void
    {
        $response = $this->post('create', 'App\Controllers\Admin\CourtsController', [
            'court' => ['name' => '   ', 'status' => Court::STATUS_AVAILABLE],
        ]);

        $this->assertStringNotContainsString('Location:', $response);
        $this->assertStringContainsString('id="new_court_form" novalidate', $response);
        $this->assertMatchesRegularExpression('/id="error_court_name">\s*O nome da quadra não pode ser vazio!/', $response);
    }

    public function test_destroy_image_removes_file_and_record(): void
    {
        $image = $this->createImageWithFile();
        $path = (new TestFileStorage())->absolutePath($image->relativePath());

        $response = $this->post('destroy', 'App\Controllers\Admin\CourtImagesController', [
            'court_id' => $this->court->id,
            'id' => $image->id,
        ]);

        $this->assertStringContainsString('Location: /admin/courts/' . $this->court->id, $response);
        $this->assertNull(CourtImage::findById($image->id));
        $this->assertFileDoesNotExist($path);
        $this->assertStringContainsString('removida com sucesso', $_SESSION['flash']['success'] ?? '');
    }

    public function test_destroy_image_shows_error_when_database_delete_fails(): void
    {
        $image = $this->createImageWithFile();
        $path = (new TestFileStorage())->absolutePath($image->relativePath());

        DatabaseFailure::on('court_images', 'DELETE');

        $response = $this->post('destroy', 'App\Controllers\Admin\CourtImagesController', [
            'court_id' => $this->court->id,
            'id' => $image->id,
        ]);

        $this->assertStringContainsString('Location: /admin/courts/' . $this->court->id, $response);
        $this->assertArrayNotHasKey('success', $_SESSION['flash'] ?? []);
        $this->assertStringContainsString('Não foi possível remover a imagem', $_SESSION['flash']['danger'] ?? '');
        $this->assertNotNull(CourtImage::findById($image->id));
        $this->assertFileExists($path);
    }

    public function test_destroy_image_of_another_court_is_not_found(): void
    {
        $image = $this->createImageWithFile();
        $other = new Court(['name' => 'Outra']);
        $other->save();

        $this->post('destroy', 'App\Controllers\Admin\CourtImagesController', [
            'court_id' => $other->id,
            'id' => $image->id,
        ]);

        $this->assertNotNull(CourtImage::findById($image->id));
        $this->assertStringContainsString('Imagem não encontrada', $_SESSION['flash']['danger'] ?? '');
    }

    public function test_student_gallery_view_shows_images(): void
    {
        $image = $this->createImageWithFile();

        $response = $this->get('show', 'App\Controllers\CourtsController', ['id' => $this->court->id]);

        $this->assertStringContainsString('Galeria: Quadra Controller Test', $response);
        $this->assertStringContainsString($image->url(), $response);
        $this->assertStringNotContainsString('Remover Imagem', $response);
    }
}
