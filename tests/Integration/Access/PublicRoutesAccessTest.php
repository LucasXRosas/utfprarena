<?php

namespace Tests\Integration\Access;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class PublicRoutesAccessTest extends TestCase
{
    private Client $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = new Client([
            'allow_redirects' => false,
            'base_uri'        => 'http://web:80'
        ]);
    }

    public function test_should_access_home_route(): void
    {
        $response = $this->client->get('/');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('UTFPR Arena Beach Tennis', (string) $response->getBody());
    }

    public function test_should_access_login_route(): void
    {
        $response = $this->client->get('/login');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Autenticação de Acesso', (string) $response->getBody());
    }
}
