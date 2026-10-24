<?php

namespace App\Controllers\Admin;

use App\Models\Court;
use App\Models\Invoice;
use App\Models\User;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class UsersController extends Controller
{
    /**
     * RESTful: GET /admin/users
     * Lista todos os usuários de forma paginada e exibe métricas da arena.
     */
    public function index(Request $request): void
    {
        $user = $this->currentUser();
        $title = 'Painel Gerencial - Gestão de Atletas';

        $page = (int) $request->getParam('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        // 5 registros por página para demonstrar paginação
        $perPage = 5;
        $paginator = User::paginate(
            page: $page,
            per_page: $perPage,
            route: 'admin.users.index',
            order: 'id ASC'
        );

        $users = $paginator->registers();
        $courts = Court::all();

        // Métricas delegadas ao modelo
        $totalUsers = User::countTotal();
        $activeUsers = User::countActive();
        $blockedUsers = User::countBlocked();

        $this->render(
            'admin/users/index',
            compact(
                'title',
                'user',
                'users',
                'paginator',
                'courts',
                'totalUsers',
                'activeUsers',
                'blockedUsers'
            )
        );
    }

    /**
     * RESTful: GET /admin/users/new
     * Exibe o formulário de cadastro de novo usuário.
     */
    public function new(Request $request): void
    {
        $title = 'Cadastrar Novo Atleta - UTFPR Arena';
        $targetUser = new User([
            'role' => User::ROLE_STUDENT,
            'status' => User::STATUS_ACTIVE
        ]);

        $this->render('admin/users/new', compact('title', 'targetUser'));
    }

    /**
     * RESTful: POST /admin/users
     * Processa a criação de um novo usuário com validação explícita de parâmetros.
     */
    public function create(Request $request): void
    {
        $name = trim((string) $this->extractParam($request, 'name'));
        $email = trim((string) $this->extractParam($request, 'email'));
        $password = (string) $this->extractParam($request, 'password');
        $passwordConfirmation = (string) $this->extractParam($request, 'password_confirmation');
        $phone = trim((string) $this->extractParam($request, 'phone'));
        $cpf = trim((string) $this->extractParam($request, 'cpf'));
        $role = (string) $this->extractParam($request, 'role', User::ROLE_STUDENT);
        $status = (string) $this->extractParam($request, 'status', User::STATUS_ACTIVE);

        // Validação explícita de campos obrigatórios
        if ($name === '' || $email === '' || $password === '' || $passwordConfirmation === '') {
            FlashMessage::danger('Todos os campos obrigatórios (nome, e-mail, senha e confirmação) devem ser preenchidos.');
            $this->redirectTo(route('admin.users.new'));
            return;
        }

        if ($password !== $passwordConfirmation) {
            FlashMessage::danger('As senhas devem ser idênticas!');
            $this->redirectTo(route('admin.users.new'));
            return;
        }

        $targetUser = new User([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
            'phone' => $phone !== '' ? $phone : null,
            'cpf' => $cpf !== '' ? $cpf : null,
            'role' => in_array($role, [User::ROLE_STUDENT, User::ROLE_MANAGER], true) ? $role : User::ROLE_STUDENT,
            'status' => in_array($status, [User::STATUS_ACTIVE, User::STATUS_BLOCKED], true)
                ? $status
                : User::STATUS_ACTIVE
        ]);

        if (!$targetUser->save()) {
            $errorMsg = $targetUser->firstError() ?? 'Dados de cadastro inválidos.';
            FlashMessage::danger('Erro ao cadastrar: ' . $errorMsg);
            $this->redirectTo(route('admin.users.new'));
            return;
        }

        FlashMessage::success('Atleta ' . $targetUser->name . ' cadastrado com sucesso!');
        $this->redirectTo(route('admin.users.index'));
    }

    /**
     * RESTful: GET /admin/users/{id}
     * Exibe o perfil detalhado do atleta e seu histórico de faturas.
     */
    public function show(Request $request): void
    {
        $id = $this->validateIdParam($request);
        if ($id === null) {
            return;
        }

        $targetUser = User::findById($id);
        if ($targetUser === null) {
            FlashMessage::danger('Usuário não encontrado.');
            $this->redirectTo(route('admin.users.index'));
            return;
        }

        $title = 'Credencial do Atleta: ' . $targetUser->name;
        $invoices = Invoice::allByUser($targetUser->id);

        $this->render('admin/users/show', compact('title', 'targetUser', 'invoices'));
    }

    /**
     * RESTful: GET /admin/users/{id}/edit
     * Exibe o formulário de edição de dados do usuário.
     */
    public function edit(Request $request): void
    {
        $id = $this->validateIdParam($request);
        if ($id === null) {
            return;
        }

        $targetUser = User::findById($id);
        if ($targetUser === null) {
            FlashMessage::danger('Usuário não encontrado.');
            $this->redirectTo(route('admin.users.index'));
            return;
        }

        $title = 'Editar Atleta: ' . $targetUser->name;
        $this->render('admin/users/edit', compact('title', 'targetUser'));
    }

    /**
     * RESTful: PUT /admin/users/{id}
     * Processa a atualização dos dados do usuário.
     */
    public function update(Request $request): void
    {
        $id = $this->validateIdParam($request);
        if ($id === null) {
            return;
        }

        $targetUser = User::findById($id);
        if ($targetUser === null) {
            FlashMessage::danger('Usuário não encontrado.');
            $this->redirectTo(route('admin.users.index'));
            return;
        }

        $name = trim((string) $this->extractParam($request, 'name'));
        $email = trim((string) $this->extractParam($request, 'email'));
        $phone = trim((string) $this->extractParam($request, 'phone'));
        $cpf = trim((string) $this->extractParam($request, 'cpf'));
        $role = (string) $this->extractParam($request, 'role', $targetUser->role);
        $status = (string) $this->extractParam($request, 'status', $targetUser->status);
        $password = (string) $this->extractParam($request, 'password', '');
        $passwordConfirmation = (string) $this->extractParam($request, 'password_confirmation', '');

        if ($name === '' || $email === '') {
            FlashMessage::danger('Nome e e-mail são obrigatórios.');
            $this->redirectTo(route('admin.users.edit', ['id' => $id]));
            return;
        }

        // Atualização de senha opcional
        if ($password !== '') {
            if ($password !== $passwordConfirmation) {
                FlashMessage::danger('As senhas devem ser idênticas!');
                $this->redirectTo(route('admin.users.edit', ['id' => $id]));
                return;
            }
            $targetUser->changePassword($password);
        }

        $targetUser->name = $name;
        $targetUser->email = $email;
        $targetUser->phone = $phone !== '' ? $phone : null;
        $targetUser->cpf = $cpf !== '' ? $cpf : null;

        if (in_array($role, [User::ROLE_STUDENT, User::ROLE_MANAGER], true)) {
            $targetUser->role = $role;
        }

        if (in_array($status, [User::STATUS_ACTIVE, User::STATUS_BLOCKED], true)) {
            $targetUser->status = $status;
        }

        if (!$targetUser->save()) {
            $errorMsg = $targetUser->firstError() ?? 'Dados inválidos.';
            FlashMessage::danger('Erro ao atualizar: ' . $errorMsg);
            $this->redirectTo(route('admin.users.edit', ['id' => $id]));
            return;
        }

        FlashMessage::success('Usuário ' . $targetUser->name . ' atualizado com sucesso!');
        $this->redirectTo(route('admin.users.index'));
    }

    /**
     * RESTful: DELETE /admin/users/{id}
     * Remove um usuário do sistema.
     */
    public function destroy(Request $request): void
    {
        $id = $this->validateIdParam($request);
        if ($id === null) {
            return;
        }

        $targetUser = User::findById($id);
        if ($targetUser === null) {
            FlashMessage::danger('Usuário não encontrado.');
            $this->redirectTo(route('admin.users.index'));
            return;
        }

        // Impede que o próprio administrador logado se delete
        $loggedUser = $this->currentUser();
        if ($loggedUser !== null && $loggedUser->id === $targetUser->id) {
            FlashMessage::danger('Não é permitido remover o próprio administrador conectado.');
            $this->redirectTo(route('admin.users.index'));
            return;
        }

        $name = $targetUser->name;
        $targetUser->destroy();

        FlashMessage::success('Usuário ' . $name . ' foi removido com sucesso!');
        $this->redirectTo(route('admin.users.index'));
    }

    /**
     * Ação de controle de acesso: POST /admin/users/{id}/block
     */
    public function block(Request $request): void
    {
        $id = $this->validateIdParam($request);
        if ($id === null) {
            return;
        }

        $targetUser = User::findById($id);
        if ($targetUser === null) {
            FlashMessage::danger('Usuário não encontrado.');
            $this->redirectTo(route('admin.users.index'));
            return;
        }

        $targetUser->block();
        $targetUser->save();
        FlashMessage::danger('Acesso do usuário ' . $targetUser->name . ' foi bloqueado.');

        $this->redirectTo(route('admin.users.index'));
    }

    /**
     * Ação de controle de acesso: POST /admin/users/{id}/activate
     */
    public function activate(Request $request): void
    {
        $id = $this->validateIdParam($request);
        if ($id === null) {
            return;
        }

        $targetUser = User::findById($id);
        if ($targetUser === null) {
            FlashMessage::danger('Usuário não encontrado.');
            $this->redirectTo(route('admin.users.index'));
            return;
        }

        $targetUser->activate();
        $targetUser->save();
        FlashMessage::success('Acesso do usuário ' . $targetUser->name . ' foi reativado.');

        $this->redirectTo(route('admin.users.index'));
    }

    private function validateIdParam(Request $request): ?int
    {
        $idParam = $request->getParam('id');
        if ($idParam === null || !is_numeric($idParam)) {
            FlashMessage::danger('Parâmetro id obrigatório ausente ou inválido.');
            $this->redirectTo(route('admin.users.index'));
            return null;
        }

        return (int) $idParam;
    }

    private function extractParam(Request $request, string $key, mixed $default = null): mixed
    {
        if ($request->hasParam("user.{$key}")) {
            return $request->getParam("user.{$key}");
        }

        if ($request->hasParam($key)) {
            return $request->getParam($key);
        }

        return $default;
    }
}
