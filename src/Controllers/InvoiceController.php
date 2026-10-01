<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Invoice;
use DateTimeImmutable;

/**
 * InvoiceController - Gerenciamento de Faturas e Mensalidades.
 *
 * Responsabilidades:
 *  - Listar faturas de um usuario.
 *  - Criar nova fatura mensal.
 *  - Registrar pagamento de uma fatura (dispara reavaliacao de acesso).
 */
class InvoiceController extends BaseController
{
    /**
     * GET /users/{userId}/invoices
     * Lista todas as faturas de um usuario.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function index(Request $request, array $params = []): Response
    {
        $userId = (int)($params['userId'] ?? 0);

        if ($userId <= 0) {
            return $this->json(['status' => 'error', 'message' => 'ID de usuario invalido.'], 400);
        }

        // TODO: Buscar faturas do usuario no banco de dados
        $today = new DateTimeImmutable('today');
        $invoices = [
            new Invoice(1, $userId, 150.0, $today->modify('-60 days')),
            new Invoice(2, $userId, 150.0, $today->modify('-30 days')),
            new Invoice(3, $userId, 150.0, $today->modify('+0 days')),
        ];

        return $this->json([
            'status' => 'success',
            'user_id' => $userId,
            'data' => array_map(fn (Invoice $i) => $i->toArray(), $invoices),
            'total' => count($invoices),
        ]);
    }

    /**
     * POST /users/{userId}/invoices
     * Gera uma nova fatura mensal para um usuario.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function store(Request $request, array $params = []): Response
    {
        $userId = (int)($params['userId'] ?? 0);
        $body = $request->getBody();

        if ($userId <= 0) {
            return $this->json(['status' => 'error', 'message' => 'ID de usuario invalido.'], 400);
        }

        if (empty($body['amount']) || empty($body['due_date'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Campos "amount" e "due_date" sao obrigatorios.',
            ], 422);
        }

        $dueDate = DateTimeImmutable::createFromFormat('Y-m-d', (string)$body['due_date']);
        if ($dueDate === false) {
            return $this->json([
                'status' => 'error',
                'message' => 'Formato de data invalido. Use: YYYY-MM-DD.',
            ], 422);
        }

        $invoice = new Invoice(
            id: null,
            userId: $userId,
            amount: (float)$body['amount'],
            dueDate: $dueDate,
        );

        // TODO: Persistir fatura no banco de dados

        return $this->json([
            'status' => 'success',
            'message' => 'Fatura criada com sucesso.',
            'data' => $invoice->toArray(),
        ], 201);
    }

    /**
     * PUT /invoices/{id}/pay
     * Registra o pagamento de uma fatura e reavalia o status de acesso do usuario.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function pay(Request $request, array $params = []): Response
    {
        $id = (int)($params['id'] ?? 0);

        if ($id <= 0) {
            return $this->json(['status' => 'error', 'message' => 'ID de fatura invalido.'], 400);
        }

        // TODO: Buscar fatura no banco, marcar como paga e reavaliar status do usuario
        // Regra: se usuario ficar com < 2 faturas vencidas -> reativar automaticamente

        return $this->json([
            'status' => 'success',
            'message' => "Pagamento da fatura {$id} registrado com sucesso.",
            'invoice_id' => $id,
            'paid_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
