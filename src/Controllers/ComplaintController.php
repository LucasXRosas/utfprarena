<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Complaint;

/**
 * ComplaintController - Modulo de Registro de Reclamacoes (Problem Tracker).
 *
 * Modulo ensinado no topico "Ambiente de Desenvolvimento / Problem Track":
 *  - Primeiro construido de forma estruturada (procedural PHP).
 *  - Depois refatorado para Orientacao a Objetos (OO).
 *
 * Responsabilidades:
 *  - Listar todas as reclamacoes registradas.
 *  - Registrar nova reclamacao.
 *  - Exibir detalhes de uma reclamacao.
 *  - Atualizar o status de uma reclamacao.
 */
class ComplaintController extends BaseController
{
    /**
     * GET /complaints
     * Lista todas as reclamacoes.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function index(Request $request, array $params = []): Response
    {
        // TODO: Buscar reclamacoes do banco de dados
        $complaints = [
            new Complaint(1, 'Quadra com infiltracao', 'A quadra 3 esta com agua acumulada.', 'aluno@email.com'),
            new Complaint(2, 'Vestiario sem agua quente', 'Vestiario masculino sem agua quente ha 3 dias.', 'professor@arena.com', Complaint::STATUS_IN_PROGRESS),
        ];

        return $this->json([
            'status' => 'success',
            'data' => array_map(fn (Complaint $c) => $c->toArray(), $complaints),
            'total' => count($complaints),
        ]);
    }

    /**
     * POST /complaints
     * Registra uma nova reclamacao.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function store(Request $request, array $params = []): Response
    {
        $body = $request->getBody();

        $required = ['title', 'description', 'author_email'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                return $this->json([
                    'status' => 'error',
                    'message' => "Campo obrigatorio ausente: {$field}",
                ], 422);
            }
        }

        $complaint = new Complaint(
            id: null,
            title: (string)$body['title'],
            description: (string)$body['description'],
            authorEmail: (string)$body['author_email'],
        );

        // TODO: Persistir no banco de dados

        return $this->json([
            'status' => 'success',
            'message' => 'Reclamacao registrada com sucesso.',
            'data' => $complaint->toArray(),
        ], 201);
    }

    /**
     * GET /complaints/{id}
     * Exibe os detalhes de uma reclamacao especifica.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function show(Request $request, array $params = []): Response
    {
        $id = (int)($params['id'] ?? 0);

        if ($id <= 0) {
            return $this->json(['status' => 'error', 'message' => 'ID invalido.'], 400);
        }

        // TODO: Buscar reclamacao pelo ID no banco de dados
        $complaint = new Complaint($id, 'Exemplo', 'Descricao de exemplo', 'aluno@email.com');

        return $this->json([
            'status' => 'success',
            'data' => $complaint->toArray(),
        ]);
    }

    /**
     * PUT /complaints/{id}
     * Atualiza o status de uma reclamacao.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function update(Request $request, array $params = []): Response
    {
        $id = (int)($params['id'] ?? 0);
        $body = $request->getBody();

        if ($id <= 0) {
            return $this->json(['status' => 'error', 'message' => 'ID invalido.'], 400);
        }

        $validStatuses = [Complaint::STATUS_OPEN, Complaint::STATUS_IN_PROGRESS, Complaint::STATUS_RESOLVED];
        $newStatus = (string)($body['status'] ?? '');

        if (!in_array($newStatus, $validStatuses, true)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Status invalido. Use: open, in_progress ou resolved.',
            ], 422);
        }

        // TODO: Atualizar status no banco de dados

        return $this->json([
            'status' => 'success',
            'message' => "Reclamacao {$id} atualizada para status '{$newStatus}'.",
            'id' => $id,
            'new_status' => $newStatus,
        ]);
    }
}
