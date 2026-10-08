<?php

namespace App\Controllers;

use App\Models\User;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class AuthController extends Controller
{
    public function new(Request $request): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user !== null && $user->isAdmin()) {
                $this->redirectTo(route('admin.index'));
                return;
            }
            $this->redirectTo(route('dashboard'));
            return;
        }

        $title = 'Entrar na Arena';
        $this->render('auth/login', compact('title'));
    }

    public function create(Request $request): void
    {
        $params = $request->getParams();
        $email = trim((string) ($params['user']['email'] ?? $params['email'] ?? ''));
        $password = (string) ($params['user']['password'] ?? $params['password'] ?? '');

        if ($email === '' || $password === '') {
            FlashMessage::danger('Email e senha são obrigatórios.');
            $this->redirectTo(route('users.login'));
            return;
        }

        $user = User::findByEmail($email);

        if ($user === null || !$user->authenticate($password)) {
            FlashMessage::danger('Email ou senha inválidos');
            $this->redirectTo(route('users.login'));
            return;
        }

        if ($user->isBlocked()) {
            FlashMessage::danger('Acesso bloqueado: usuário possui pendências financeiras. Procure a secretaria da arena.');
            $this->redirectTo(route('users.login'));
            return;
        }

        $user->last_login_at = date('Y-m-d H:i:s');
        $user->save();

        Auth::login($user);
        FlashMessage::success('Autenticação bem-sucedida! Bem-vindo(a), ' . $user->name . '.');

        if ($user->isAdmin()) {
            $this->redirectTo(route('admin.index'));
            return;
        }

        $this->redirectTo(route('dashboard'));
    }

    public function destroy(Request $request): void
    {
        Auth::logout();
        FlashMessage::success('Logout realizado com sucesso. Sessão encerrada com segurança.');
        $this->redirectTo(route('users.login'));
    }
}
