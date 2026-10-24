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
        // 1. Identificar explicitamente a presença dos parâmetros obrigatórios
        // Evita o uso de valores padrão silenciosos ($params['email'] ?? '') para mascarar chaves ausentes
        $hasEmail = $request->hasParam('user.email') || $request->hasParam('email');
        $hasPassword = $request->hasParam('user.password') || $request->hasParam('password');

        if (!$hasEmail || !$hasPassword) {
            FlashMessage::danger('Parâmetros obrigatórios ausentes: informe email e senha.');
            $this->redirectTo(route('users.login'));
            return;
        }

        // 2. Extração segura dos parâmetros confirmados
        $rawEmail = $request->hasParam('user.email')
            ? $request->getParam('user.email')
            : $request->getParam('email');

        $rawPassword = $request->hasParam('user.password')
            ? $request->getParam('user.password')
            : $request->getParam('password');

        $email = trim((string) $rawEmail);
        $password = (string) $rawPassword;

        // 3. Validação de conteúdo: campos enviados não podem ser vazios
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
