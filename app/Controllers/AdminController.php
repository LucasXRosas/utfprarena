<?php

namespace App\Controllers;

use App\Models\User;
use Core\Database\Database;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;
use PDO;

class AdminController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->currentUser();
        $title = 'Painel Gerencial - UTFPR Arena';
        $users = User::all();

        $pdo = Database::getDatabaseConn();
        $stmtCourts = $pdo->query("SELECT * FROM courts ORDER BY id ASC");
        $courts = $stmtCourts ? $stmtCourts->fetchAll(PDO::FETCH_ASSOC) : [];

        $totalUsers = count($users);
        $activeUsers = count(array_filter($users, fn($u) => $u->isActive()));
        $blockedUsers = count(array_filter($users, fn($u) => $u->isBlocked()));

        $this->render(
            'admin/index',
            compact('title', 'user', 'users', 'courts', 'totalUsers', 'activeUsers', 'blockedUsers')
        );
    }

    public function blockUser(Request $request): void
    {
        $id = (int) $request->getParam('id');
        $targetUser = User::findById($id);

        if ($targetUser !== null) {
            $targetUser->block();
            $targetUser->save();
            FlashMessage::danger('Acesso do usuário ' . $targetUser->name . ' foi bloqueado.');
        }

        $this->redirectTo(route('admin.index'));
    }

    public function activateUser(Request $request): void
    {
        $id = (int) $request->getParam('id');
        $targetUser = User::findById($id);

        if ($targetUser !== null) {
            $targetUser->activate();
            $targetUser->save();
            FlashMessage::success('Acesso do usuário ' . $targetUser->name . ' foi reativado.');
        }

        $this->redirectTo(route('admin.index'));
    }
}
