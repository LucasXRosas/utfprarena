<?php

namespace App\Controllers\Admin;

use App\Models\Invoice;
use App\Models\User;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

/**
 * CRUD RESTful de atletas/usuários (área administrativa).
 *
 * As regras de validação ficam no model (User::validates()). Quando o save() falha,
 * o controller renderiza o formulário novamente (sem redirecionar) para preservar o que
 * foi digitado e exibir cada erro embaixo do respectivo campo.
 */
class UsersController extends Controller
{
    private const PER_PAGE = 5;

    /**
     * RESTful: GET /admin/users
     * Lista os usuários de forma paginada e exibe métricas da arena.
     */
    public function index(Request $request): void
    {
        $user = $this->currentUser();
        $title = 'Painel Gerencial - Gestão de Atletas';

        $page = max(1, (int) $request->getParam('page', 1));

        $paginator = User::paginate(
            page: $page,
            per_page: self::PER_PAGE,
            route: 'admin.users.index',
            order: 'id ASC'
        );
        $users = $paginator->registers();

        $totalUsers = User::countTotal();
        $activeUsers = User::countActive();
        $blockedUsers = User::countBlocked();

        $this->render(
            'admin/users/index',
            compact('title', 'user', 'users', 'paginator', 'totalUsers', 'activeUsers', 'blockedUsers')
        );
    }

    /**
     * RESTful: GET /admin/users/new
     */
    public function new(Request $request): void
    {
        $this->renderForm('new', new User());
    }

    /**
     * RESTful: POST /admin/users
     */
    public function create(Request $request): void
    {
        $passwords = $this->passwordParams($request);

        $targetUser = new User(array_merge($this->userParams($request), [
            'password' => $passwords['password'],
            'password_confirmation' => $passwords['password_confirmation'],
        ]));

        if (!$targetUser->save()) {
            $this->renderForm('new', $targetUser);
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
        $targetUser = $this->findUserOrRedirect($request);
        if ($targetUser === null) {
            return;
        }

        $title = 'Credencial do Atleta: ' . $targetUser->name;
        $invoices = Invoice::allByUser($targetUser->id);

        $this->render('admin/users/show', compact('title', 'targetUser', 'invoices'));
    }

    /**
     * RESTful: GET /admin/users/{id}/edit
     */
    public function edit(Request $request): void
    {
        $targetUser = $this->findUserOrRedirect($request);
        if ($targetUser === null) {
            return;
        }

        $this->renderForm('edit', $targetUser);
    }

    /**
     * RESTful: PUT /admin/users/{id}
     */
    public function update(Request $request): void
    {
        $targetUser = $this->findUserOrRedirect($request);
        if ($targetUser === null) {
            return;
        }

        foreach ($this->userParams($request) as $attribute => $value) {
            // role/status ausentes na requisição mantêm o valor atual
            if ($value !== null || !in_array($attribute, ['role', 'status'], true)) {
                $targetUser->$attribute = $value;
            }
        }

        // Senha é opcional na edição: só troca se algum dos dois campos for preenchido
        $passwords = $this->passwordParams($request);
        if ($passwords['password'] !== '' || $passwords['password_confirmation'] !== '') {
            $targetUser->updatePassword($passwords['password'], $passwords['password_confirmation']);
        }

        // O admin logado não pode se rebaixar nem se bloquear (perderia o acesso ao painel)
        if ($this->isCurrentUser($targetUser) && (!$targetUser->isAdmin() || !$targetUser->isActive())) {
            $targetUser->isValid(); // coleta os demais erros do formulário (isValid() limpa os anteriores)

            if (!$targetUser->isAdmin()) {
                $targetUser->addError('role', 'você não pode remover o seu próprio perfil de administrador!');
            }
            if (!$targetUser->isActive()) {
                $targetUser->addError('status', 'você não pode bloquear o seu próprio acesso!');
            }

            $this->renderForm('edit', $targetUser);
            return;
        }

        if (!$targetUser->save()) {
            $this->renderForm('edit', $targetUser);
            return;
        }

        FlashMessage::success('Usuário ' . $targetUser->name . ' atualizado com sucesso!');
        $this->redirectTo(route('admin.users.index'));
    }

    /**
     * RESTful: DELETE /admin/users/{id}
     */
    public function destroy(Request $request): void
    {
        $targetUser = $this->findUserOrRedirect($request);
        if ($targetUser === null) {
            return;
        }

        if ($this->isCurrentUser($targetUser)) {
            FlashMessage::danger('Não é permitido remover o próprio administrador conectado.');
            $this->redirectTo(route('admin.users.index'));
            return;
        }

        $targetUser->destroy();

        FlashMessage::success('Usuário ' . $targetUser->name . ' foi removido com sucesso!');
        $this->redirectTo(route('admin.users.index'));
    }

    /**
     * Controle de acesso (catraca): POST /admin/users/{id}/block
     */
    public function block(Request $request): void
    {
        $targetUser = $this->findUserOrRedirect($request);
        if ($targetUser === null) {
            return;
        }

        if ($this->isCurrentUser($targetUser)) {
            FlashMessage::danger('Não é permitido bloquear o próprio administrador conectado.');
            $this->redirectTo(route('admin.users.index'));
            return;
        }

        // update() grava só o status: não depende da validação dos demais campos
        $targetUser->update(['status' => User::STATUS_BLOCKED]);
        FlashMessage::danger('Acesso do usuário ' . $targetUser->name . ' foi bloqueado.');
        $this->redirectTo(route('admin.users.index'));
    }

    /**
     * Controle de acesso (catraca): POST /admin/users/{id}/activate
     */
    public function activate(Request $request): void
    {
        $targetUser = $this->findUserOrRedirect($request);
        if ($targetUser === null) {
            return;
        }

        $targetUser->update(['status' => User::STATUS_ACTIVE]);
        FlashMessage::success('Acesso do usuário ' . $targetUser->name . ' foi reativado.');
        $this->redirectTo(route('admin.users.index'));
    }

    /* ---------------- helpers ---------------- */

    /**
     * Renderiza o formulário de cadastro/edição. Com erros de validação, responde 422.
     */
    private function renderForm(string $view, User $targetUser): void
    {
        if ($targetUser->hasErrors()) {
            http_response_code(422);
            FlashMessage::danger('Não foi possível salvar. Corrija os campos destacados.');
        }

        $title = $targetUser->newRecord()
            ? 'Cadastrar Novo Atleta - UTFPR Arena'
            : 'Editar Atleta #' . $targetUser->id;

        $this->render('admin/users/' . $view, compact('title', 'targetUser'));
    }

    private function findUserOrRedirect(Request $request): ?User
    {
        $id = $request->getParam('id');
        $targetUser = is_numeric($id) ? User::findById((int) $id) : null;

        if ($targetUser === null) {
            FlashMessage::danger('Usuário não encontrado.');
            $this->redirectTo(route('admin.users.index'));
            return null;
        }

        return $targetUser;
    }

    private function isCurrentUser(User $targetUser): bool
    {
        $loggedUser = $this->currentUser();
        return $loggedUser !== null && $loggedUser->id === $targetUser->id;
    }

    /**
     * Lista explícita dos campos aceitos do formulário (evita atribuição em massa de colunas
     * sensíveis como encrypted_password ou remember_token).
     *
     * @return array<string, ?string>
     */
    private function userParams(Request $request): array
    {
        $data = $request->getParam('user', []);
        $data = is_array($data) ? $data : [];

        $text = function (string $key) use ($data): ?string {
            $value = trim((string) ($data[$key] ?? ''));
            return $value === '' ? null : $value;
        };

        $email = $text('email');

        return [
            'name' => $text('name'),
            'email' => $email !== null ? mb_strtolower($email) : null,
            'phone' => $text('phone'),
            'cpf' => $text('cpf'),
            'role' => $text('role'),
            'status' => $text('status'),
        ];
    }

    /**
     * Senhas não passam por trim(): espaços fazem parte da senha.
     *
     * @return array{password: string, password_confirmation: string}
     */
    private function passwordParams(Request $request): array
    {
        return [
            'password' => (string) $request->getParam('user.password', ''),
            'password_confirmation' => (string) $request->getParam('user.password_confirmation', ''),
        ];
    }
}
