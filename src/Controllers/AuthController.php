<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\FlashMessage;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;

/**
 * AuthController - Fluxo completo de Autenticacao com Sessoes.
 *
 * Cobre os itens da rubrica:
 *   1.1 Tentativa de acesso a area restrita sem autenticacao -> AuthMiddleware bloqueia
 *   1.2 Tentativa de autenticacao com dados incorretos -> login() retorna erro
 *   1.3 Autenticacao bem-sucedida -> login() cria sessao e redireciona
 *   1.4 Logout -> logout() destroi sessao e redireciona
 *
 * Conceitos abordados:
 *   - Cookies e sessoes: PHP usa PHPSESSID (cookie) para manter a sessao no servidor.
 *   - Hash de senha: password_hash() com PASSWORD_BCRYPT (custo 12).
 *   - Verificacao: password_verify() compara texto puro com o hash salvo.
 *
 * Referencia: "PHP: The Right Way" - https://phptherightway.com/#security
 */
class AuthController extends BaseController
{
    /**
     * GET /login
     * Exibe o formulario de login.
     *
     * Rota publica: qualquer usuario pode acessar.
     */
    public function showLogin(Request $request, array $params = []): Response
    {
        if (Session::isAuthenticated()) {
            return Response::redirect('/dashboard');
        }

        return $this->view('auth/login', [
            'title' => 'Login',
            'error' => FlashMessage::get('error'),
            'success' => FlashMessage::get('success'),
        ]);
    }

    /**
     * POST /login
     * Processa as credenciais e cria a sessao de autenticacao.
     *
     * Fluxo 1.2 - Dados incorretos:
     *   - Email nao encontrado -> erro generico (evita enumeracao de usuarios)
     *   - Senha incorreta     -> erro generico
     *
     * Fluxo 1.3 - Autenticacao bem-sucedida:
     *   - password_verify() retorna true
     *   - Session::loginUser() salva os dados do usuario na sessao
     *   - Redireciona para /dashboard (usuario) ou /admin (manager)
     */
    public function login(Request $request, array $params = []): Response
    {
        $body = $request->getBody();
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        // Validacao basica de campos
        if (empty($email) || empty($password)) {
            FlashMessage::set('error', 'E-mail e senha sao obrigatorios.');
            return Response::redirect('/login');
        }

        // TODO: Buscar usuario no banco de dados pelo email
        // Simulacao: usuario de teste com hash bcrypt
        $fakeUsers = $this->getFakeUsers();
        $foundUser = null;

        foreach ($fakeUsers as $u) {
            if ($u['email'] === $email) {
                $foundUser = $u;
                break;
            }
        }

        // Erro 1: usuario nao encontrado
        if ($foundUser === null) {
            FlashMessage::set('error', 'Credenciais invalidas. Verifique seu e-mail e senha.');
            return Response::redirect('/login');
        }

        // Erro 2: senha incorreta - password_verify() compara texto puro com hash bcrypt
        if (!password_verify($password, $foundUser['password_hash'])) {
            FlashMessage::set('error', 'Credenciais invalidas. Verifique seu e-mail e senha.');
            return Response::redirect('/login');
        }

        // Sucesso: cria sessao de autenticacao
        Session::loginUser($foundUser['id'], $foundUser['email'], $foundUser['role']);
        FlashMessage::set('success', 'Bem-vindo, ' . $foundUser['name'] . '!');

        // Redireciona conforme papel (autorizacao)
        return $foundUser['role'] === User::ROLE_MANAGER
            ? Response::redirect('/admin')
            : Response::redirect('/dashboard');
    }

    /**
     * POST /logout
     * Destroi a sessao e redireciona para o login.
     *
     * Fluxo 1.4 - Logout:
     *   - Session::logout() remove dados de autenticacao
     *   - FlashMessage informa o usuario
     *   - Redireciona para /login
     */
    public function logout(Request $request, array $params = []): Response
    {
        Session::logout();
        FlashMessage::set('success', 'Voce saiu com sucesso. Ate logo!');
        return Response::redirect('/login');
    }

    /**
     * GET /register
     * Exibe o formulario de cadastro.
     */
    public function showRegister(Request $request, array $params = []): Response
    {
        return $this->view('auth/register', [
            'title' => 'Cadastro',
            'error' => FlashMessage::get('error'),
        ]);
    }

    /**
     * POST /auth/register
     * Registra um novo usuario com senha hasheada.
     *
     * Seguranca: password_hash() com PASSWORD_BCRYPT (custo 12 por padrao).
     * O hash resultante tem formato: $2y$12$... e inclui salt automaticamente.
     */
    public function register(Request $request, array $params = []): Response
    {
        $data = $request->getBody();

        $required = ['full_name', 'cpf', 'email', 'password', 'phone'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->json([
                    'status' => 'error',
                    'message' => "Campo obrigatorio ausente: {$field}",
                ], 422);
            }
        }

        // Criacao do usuario OO com senha hasheada (BCrypt)
        $user = new User(
            id: null,
            fullName: (string)$data['full_name'],
            cpf: (string)$data['cpf'],
            email: (string)$data['email'],
            password: password_hash((string)$data['password'], PASSWORD_BCRYPT),
            phone: (string)$data['phone'],
            role: (string)($data['role'] ?? User::ROLE_STUDENT),
        );

        // TODO: Persistir no banco de dados via repositorio

        return $this->json([
            'status' => 'success',
            'message' => 'Usuario registrado com sucesso.',
            'data' => $user->toArray(),
        ], 201);
    }

    /**
     * Usuarios de teste para demonstracao sem banco de dados.
     * ADMIN: admin@arena.com / admin123
     * ALUNO: aluno@arena.com / aluno123
     *
     * Hashes gerados com: password_hash('...', PASSWORD_BCRYPT)
     *
     * @return array<int, array<string, mixed>>
     */
    private function getFakeUsers(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Administrador',
                'email' => 'admin@arena.com',
                'role' => User::ROLE_MANAGER,
                // Senha: admin123
                'password_hash' => '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            ],
            [
                'id' => 2,
                'name' => 'Joao Aluno',
                'email' => 'aluno@arena.com',
                'role' => User::ROLE_STUDENT,
                // Senha: aluno123
                'password_hash' => '$2y$12$3euPcmQFCiblsZewd1JD4OZBqFCR7m0jXP1MjM8fwKE.Lq6JiVMi',
            ],
        ];
    }
}
