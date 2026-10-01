<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;

/**
 * AuthController - Modulo de Autenticacao (Cadastro e Login).
 *
 * Responsabilidades:
 *  - Registrar novos usuarios no sistema.
 *  - Autenticar credenciais e retornar token de acesso.
 */
class AuthController extends BaseController
{
    /**
     * POST /auth/register
     * Registra um novo usuario no sistema.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function register(Request $request, array $params = []): Response
    {
        $data = $request->getBody();

        // Validacao dos campos obrigatorios
        $required = ['full_name', 'cpf', 'email', 'password', 'phone'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->json([
                    'status' => 'error',
                    'message' => "Campo obrigatorio ausente: {$field}",
                ], 422);
            }
        }

        // TODO: Persistir no banco de dados via repositorio
        $user = new User(
            id: null,
            fullName: (string)$data['full_name'],
            cpf: (string)$data['cpf'],
            email: (string)$data['email'],
            password: password_hash((string)$data['password'], PASSWORD_BCRYPT),
            phone: (string)$data['phone'],
            role: (string)($data['role'] ?? User::ROLE_STUDENT),
        );

        return $this->json([
            'status' => 'success',
            'message' => 'Usuario registrado com sucesso.',
            'data' => $user->toArray(),
        ], 201);
    }

    /**
     * POST /auth/login
     * Autentica o usuario e retorna token JWT simulado.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function login(Request $request, array $params = []): Response
    {
        $data = $request->getBody();

        if (empty($data['email']) || empty($data['password'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'E-mail e senha sao obrigatorios.',
            ], 422);
        }

        // TODO: Buscar usuario no banco de dados e validar hash da senha
        // Simulacao para desenvolvimento
        return $this->json([
            'status' => 'success',
            'message' => 'Login realizado com sucesso.',
            'token' => 'jwt_token_simulado_' . base64_encode((string)$data['email']),
            'expires_in' => 86400,
        ]);
    }
}
