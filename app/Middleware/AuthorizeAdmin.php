<?php

namespace App\Middleware;

use Core\Http\Middleware\Middleware;
use Core\Http\Request;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class AuthorizeAdmin implements Middleware
{
    public function handle(Request $request): void
    {
        $user = Auth::user();
        if ($user === null || !$user->isAdmin()) {
            if ($request->acceptJson()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode(['error' => 'Acesso proibido: privilégios insuficientes', 'code' => 403]);
                exit;
            }

            FlashMessage::danger('Acesso não autorizado: privilégios de administrador necessários');
            $this->redirectTo(route('dashboard'));
        }
    }

    private function redirectTo(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
