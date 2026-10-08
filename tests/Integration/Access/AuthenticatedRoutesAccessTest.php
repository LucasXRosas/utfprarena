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
            'base_uri' => 'http://web:8080'
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
