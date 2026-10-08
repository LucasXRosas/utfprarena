<?php

namespace Tests\Integration\Controllers;

class HomeControllerTest extends ControllerTestCase
{
    public function test_render_home_page(): void
    {
        $response = $this->get(
            action: 'index',
            controllerName: 'App\Controllers\HomeController'
        );

        $this->assertMatchesRegularExpression('/UTFPR Arena Beach Tennis/', $response);
        $this->assertMatchesRegularExpression('/Quadras de Areia/', $response);
    }
}
