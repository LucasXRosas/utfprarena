<?php

namespace App\Controllers;

use Core\Database\Database;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use PDO;

class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->currentUser();
        $title = 'Área do Aluno - Arena Beach Tennis';

        $pdo = Database::getDatabaseConn();

        // Buscar quadras disponíveis
        $stmtCourts = $pdo->query("SELECT * FROM courts ORDER BY id ASC");
        $courts = $stmtCourts ? $stmtCourts->fetchAll(PDO::FETCH_ASSOC) : [];

        // Buscar faturas do usuário logado
        $invoices = [];
        if ($user !== null && $user->id !== null) {
            $stmtInv = $pdo->prepare("SELECT * FROM invoices WHERE user_id = :uid ORDER BY due_date DESC");
            $stmtInv->execute([':uid' => $user->id]);
            $invoices = $stmtInv->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->render('dashboard/index', compact('title', 'user', 'courts', 'invoices'));
    }
}
