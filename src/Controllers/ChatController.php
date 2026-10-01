<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\ChatMessage;

/**
 * ChatController - Modulo de Chat em PHP Orientado a Objetos.
 *
 * Modulo ensinado no topico "Ambiente de Desenvolvimento / Chat":
 *  - Primeiro construido de forma estruturada (procedural PHP).
 *  - Depois refatorado para Orientacao a Objetos (OO).
 *
 * Responsabilidades:
 *  - Listar mensagens do chat.
 *  - Registrar novas mensagens.
 */
class ChatController extends BaseController
{
    /**
     * GET /chat/messages
     * Lista as mensagens do chat.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function index(Request $request, array $params = []): Response
    {
        // TODO: Buscar mensagens do banco de dados via repositorio
        // Simulacao com dados em memoria para desenvolvimento inicial
        $messages = [
            new ChatMessage(1, 'admin@arena.com', 'Bem-vindo ao chat da Arena UTFPR!'),
            new ChatMessage(2, 'aluno@email.com', 'Oi, tenho uma duvida sobre minha mensalidade.'),
            new ChatMessage(3, 'admin@arena.com', 'Claro! Pode falar.'),
        ];

        $data = array_map(fn (ChatMessage $m) => $m->toArray(), $messages);

        return $this->json([
            'status' => 'success',
            'data' => $data,
            'total' => count($data),
        ]);
    }

    /**
     * POST /chat/messages
     * Registra uma nova mensagem no chat.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function store(Request $request, array $params = []): Response
    {
        $body = $request->getBody();

        if (empty($body['sender']) || empty($body['message'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Os campos "sender" e "message" sao obrigatorios.',
            ], 422);
        }

        $chatMessage = new ChatMessage(
            id: null,
            sender: (string)$body['sender'],
            message: (string)$body['message'],
        );

        // TODO: Persistir mensagem no banco de dados

        return $this->json([
            'status' => 'success',
            'message' => 'Mensagem enviada com sucesso.',
            'data' => $chatMessage->toArray(),
        ], 201);
    }
}
