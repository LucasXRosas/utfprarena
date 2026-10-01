<?php

/**
 * ChatController (Versao Estruturada - Referencia Historica)
 *
 * Este arquivo documenta a forma ESTRUTURADA (procedural) do chat,
 * ensinada pelo professor no primeiro momento do topico
 * "Ambiente de Desenvolvimento / Chat".
 *
 * A versao orientada a objetos esta em: src/Controllers/ChatController.php
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * Ponto de entrada estruturado para o chat (legado / referencia de aula).
 *
 * Como era antes da refatoracao para OO:
 *
 *   <?php
 *   // Simulacao de "banco de dados" em array
 *   $messages = [
 *       ['id' => 1, 'sender' => 'admin', 'message' => 'Bem-vindo!', 'sent_at' => date('Y-m-d H:i:s')],
 *   ];
 *
 *   // Leitura da mensagem enviada por POST
 *   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 *       $sender  = $_POST['sender']  ?? '';
 *       $message = $_POST['message'] ?? '';
 *
 *       if ($sender && $message) {
 *           $messages[] = [
 *               'id' => count($messages) + 1,
 *               'sender' => $sender,
 *               'message' => $message,
 *               'sent_at' => date('Y-m-d H:i:s'),
 *           ];
 *           echo json_encode(['status' => 'success', 'data' => end($messages)]);
 *       } else {
 *           http_response_code(422);
 *           echo json_encode(['status' => 'error', 'message' => 'Campos obrigatorios.']);
 *       }
 *   } else {
 *       echo json_encode(['status' => 'success', 'data' => $messages]);
 *   }
 *
 * Problemas da abordagem estruturada:
 *  1. Codigo misturado (logica + persistencia + apresentacao no mesmo arquivo).
 *  2. Sem reuso: cada funcionalidade precisa reescrever as validacoes.
 *  3. Sem testabilidade: impossivel escrever testes unitarios para funcoes soltas.
 *  4. Sem autoload: requires manuais em cada arquivo.
 *
 * A refatoracao para OO resolve todos esses problemas atraves de:
 *  - Separacao de responsabilidades (MVC).
 *  - Heranca e reuso (BaseController).
 *  - Injecao de dependencia e testabilidade (PHPUnit).
 *  - Autoload PSR-4 via Composer.
 */
