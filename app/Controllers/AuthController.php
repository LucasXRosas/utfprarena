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
            $this->redirectTo(route($user !== null && $user->isAdmin() ? 'admin.index' : 'dashboard'));
            return;
        }

        $this->renderLogin();
    }

    /**
     * POST /login
     *
     * A validação é feita no backend (o formulário usa novalidate). Em caso de erro, a tela de login
     * é renderizada novamente mantendo o e-mail digitado e com a mensagem embaixo do campo.
     */
    public function create(Request $request): void
    {
        $email = mb_strtolower(trim((string) $request->getParam('user.email', '')));
        $password = (string) $request->getParam('user.password', ''); // senha não passa por trim()

        // 1. Validação dos campos: cada erro aparece embaixo do respectivo campo
        $errors = [];
        if ($email === '') {
            $errors['email'] = 'Informe o seu e-mail.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Informe um e-mail válido (ex: nome@arena.com).';
        }

        if ($password === '') {
            $errors['password'] = 'Informe a sua senha.';
        }

        if (!empty($errors)) {
            $this->renderLogin($email, $errors, 422);
            return;
        }

        // 2. Credenciais: mensagem genérica, sem revelar se o e-mail existe (evita enumeração de usuários)
        $user = User::findByEmail($email);
        if ($user === null || !$user->authenticate($password)) {
            FlashMessage::danger('E-mail ou senha inválidos.');
            $this->renderLogin($email, ['email' => '', 'password' => ''], 401);
            return;
        }

        if ($user->isBlocked()) {
            FlashMessage::danger('Acesso bloqueado: usuário possui pendências financeiras. Procure a secretaria da arena.');
            $this->renderLogin($email, [], 403);
            return;
        }

        // update() grava só a coluna, sem depender da validação dos demais dados do cadastro
        $user->update(['last_login_at' => date('Y-m-d H:i:s')]);

        Auth::login($user);
        FlashMessage::success('Autenticação bem-sucedida! Bem-vindo(a), ' . $user->name . '.');

        $this->redirectTo(route($user->isAdmin() ? 'admin.index' : 'dashboard'));
    }

    public function destroy(Request $request): void
    {
        Auth::logout();
        FlashMessage::success('Logout realizado com sucesso. Sessão encerrada com segurança.');
        $this->redirectTo(route('users.login'));
    }

    /**
     * @param array<string, string> $errors campo => mensagem ('' apenas destaca o campo em vermelho)
     */
    private function renderLogin(string $email = '', array $errors = [], int $status = 200): void
    {
        http_response_code($status);

        $title = 'Entrar na Arena';
        $this->render('auth/login', compact('title', 'email', 'errors'));
    }
}
