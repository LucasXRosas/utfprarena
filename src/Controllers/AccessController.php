<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Invoice;
use App\Models\User;
use DateTimeImmutable;

/**
 * AccessController - Motor de Controle de Acesso por Inadimplencia.
 *
 * Responsabilidades:
 *  - Verificar o status de acesso de um usuario com base em suas faturas vencidas.
 *  - Aplicar ou remover bloqueios manuais de acesso.
 *
 * Regra de Negocio:
 *  - 2 ou mais faturas vencidas -> status BLOQUEADO.
 *  - Menos de 2 faturas vencidas -> status ATIVO.
 */
class AccessController extends BaseController
{
    /**
     * GET /access/check/{userId}
     * Verifica o status de acesso calculado para um usuario.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function check(Request $request, array $params = []): Response
    {
        $userId = (int)($params['userId'] ?? 0);

        if ($userId <= 0) {
            return $this->json(['status' => 'error', 'message' => 'ID de usuario invalido.'], 400);
        }

        // TODO: Buscar faturas do usuario no banco de dados
        // Simulacao para desenvolvimento com dados de exemplo
        $today = new DateTimeImmutable('today');
        $invoicesSimulated = [
            new Invoice(1, $userId, 150.0, $today->modify('-35 days')),
            new Invoice(2, $userId, 150.0, $today->modify('-5 days')),
        ];

        $calculatedStatus = Invoice::evaluateAccessStatus($invoicesSimulated);

        return $this->json([
            'status' => 'success',
            'user_id' => $userId,
            'access_status' => $calculatedStatus,
            'is_blocked' => $calculatedStatus === User::STATUS_BLOCKED,
            'message' => $calculatedStatus === User::STATUS_BLOCKED
                ? 'Acesso bloqueado por inadimplencia.'
                : 'Acesso liberado.',
        ]);
    }

    /**
     * POST /access/block/{userId}
     * Aplica bloqueio manual de acesso a um usuario.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function block(Request $request, array $params = []): Response
    {
        $userId = (int)($params['userId'] ?? 0);

        if ($userId <= 0) {
            return $this->json(['status' => 'error', 'message' => 'ID de usuario invalido.'], 400);
        }

        // TODO: Atualizar status no banco de dados via repositorio

        return $this->json([
            'status' => 'success',
            'message' => "Usuario {$userId} bloqueado com sucesso.",
            'user_id' => $userId,
            'new_status' => User::STATUS_BLOCKED,
        ]);
    }

    /**
     * POST /access/activate/{userId}
     * Remove o bloqueio e reativa o acesso de um usuario.
     *
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    public function activate(Request $request, array $params = []): Response
    {
        $userId = (int)($params['userId'] ?? 0);

        if ($userId <= 0) {
            return $this->json(['status' => 'error', 'message' => 'ID de usuario invalido.'], 400);
        }

        // TODO: Atualizar status no banco de dados via repositorio

        return $this->json([
            'status' => 'success',
            'message' => "Usuario {$userId} reativado com sucesso.",
            'user_id' => $userId,
            'new_status' => User::STATUS_ACTIVE,
        ]);
    }
}
