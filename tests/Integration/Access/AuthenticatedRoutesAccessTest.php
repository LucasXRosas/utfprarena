<?php

namespace Tests\Integration\Access;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class AuthenticatedRoutesAccessTest extends TestCase
{
    private Client $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = new Client([
            'allow_redirects' => false,
            'http_errors'     => false,
            'base_uri'        => 'http://web:80'
        ]);
    }

    public function test_should_redirect_unauthenticated_user_from_dashboard(): void
    {
        $response = $this->client->get('/dashboard');

        // Middleware 'auth' redireciona com status 302 para /login
        $this->assertEquals(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('/login', $location);
    }

    public function test_should_redirect_unauthenticated_user_from_admin(): void
    {
        $response = $this->client->get('/admin');

        // Middleware 'auth' redireciona com status 302 para /login
        $this->assertEquals(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('/login', $location);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function assertRedirectsToLogin(string $method, string $uri, array $options = []): void
    {
        $response = $this->client->request($method, $uri, $options);

        $this->assertEquals(302, $response->getStatusCode(), "{$method} {$uri} deveria redirecionar");
        $this->assertStringContainsString('/login', $response->getHeaderLine('Location'));
    }

    /* ---------------- Rotas de imagens/arquivos ---------------- */

    public function test_should_redirect_unauthenticated_user_from_profile(): void
    {
        $this->assertRedirectsToLogin('GET', '/profile');
    }

    public function test_should_block_unauthenticated_avatar_upload(): void
    {
        $this->assertRedirectsToLogin('POST', '/profile/avatar', [
            'multipart' => [
                ['name' => 'avatar', 'contents' => 'conteudo', 'filename' => 'avatar.png'],
            ],
        ]);
    }

    public function test_should_block_unauthenticated_avatar_removal(): void
    {
        $this->assertRedirectsToLogin('POST', '/profile/avatar', [
            'form_params' => ['_method' => 'DELETE'],
        ]);
    }

    public function test_should_redirect_unauthenticated_user_from_court_gallery(): void
    {
        $this->assertRedirectsToLogin('GET', '/courts/1');
    }

    public function test_should_redirect_unauthenticated_user_from_admin_courts(): void
    {
        $this->assertRedirectsToLogin('GET', '/admin/courts');
        $this->assertRedirectsToLogin('GET', '/admin/courts/1');
    }

    public function test_should_block_unauthenticated_court_image_upload(): void
    {
        $this->assertRedirectsToLogin('POST', '/admin/courts/1/images', [
            'multipart' => [
                ['name' => 'image', 'contents' => 'conteudo', 'filename' => 'quadra.png'],
            ],
        ]);
    }

    public function test_should_block_unauthenticated_court_image_removal(): void
    {
        $this->assertRedirectsToLogin('POST', '/admin/courts/1/images/1', [
            'form_params' => ['_method' => 'DELETE'],
        ]);
    }

    public function test_should_block_unauthenticated_court_removal(): void
    {
        $this->assertRedirectsToLogin('POST', '/admin/courts/1', [
            'form_params' => ['_method' => 'DELETE'],
        ]);
    }

    /* ---------------- Rotas da relação NxN (turmas e matrículas) ---------------- */

    public function test_should_redirect_unauthenticated_user_from_classes(): void
    {
        $this->assertRedirectsToLogin('GET', '/admin/classes');
        $this->assertRedirectsToLogin('GET', '/admin/classes/new');
        $this->assertRedirectsToLogin('GET', '/admin/classes/1');
    }

    public function test_should_block_unauthenticated_class_creation_and_removal(): void
    {
        $this->assertRedirectsToLogin('POST', '/admin/classes', [
            'form_params' => ['training_class' => ['name' => 'Invasora', 'schedule' => 'Domingo 8h']],
        ]);
        $this->assertRedirectsToLogin('POST', '/admin/classes/4', [
            'form_params' => ['_method' => 'DELETE'],
        ]);
    }

    public function test_should_block_unauthenticated_enrollment_registration_and_removal(): void
    {
        $this->assertRedirectsToLogin('POST', '/admin/classes/1/enrollments', [
            'form_params' => ['user_id' => 2],
        ]);
        $this->assertRedirectsToLogin('DELETE', '/admin/classes/1/enrollments/2');
    }

    public function test_should_return_401_json_for_unauthenticated_ajax_enrollment(): void
    {
        // Requisição Ajax (Accept: application/json) sem sessão: 401 em JSON, sem redirect
        foreach (['POST' => '/admin/classes/1/enrollments', 'DELETE' => '/admin/classes/1/enrollments/2'] as $method => $uri) {
            $response = $this->client->request($method, $uri, [
                'headers' => ['Accept' => 'application/json'],
            ]);

            $this->assertEquals(401, $response->getStatusCode(), "{$method} {$uri} deveria retornar 401");
            $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
            $this->assertEquals(401, json_decode((string) $response->getBody(), true)['code'] ?? null);
        }
    }

    public function test_should_return_401_for_unauthenticated_json_request(): void
    {
        $response = $this->client->get('/dashboard', [
            'headers' => ['Accept' => 'application/json']
        ]);

        $this->assertEquals(401, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('401', $body);
    }
}
