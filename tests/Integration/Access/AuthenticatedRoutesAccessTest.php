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
