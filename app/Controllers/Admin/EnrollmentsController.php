<?php

namespace App\Controllers\Admin;

use App\Models\TrainingClass;
use App\Models\User;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

/**
 * Matrículas: registro e remoção da relação NxN (linhas da tabela pivô class_enrollments).
 * Recurso aninhado: /admin/classes/{class_id}/enrollments
 *
 * As ações são chamadas via Ajax (cabeçalho "Accept: application/json") e respondem JSON,
 * sem recarregar a página. Sem JavaScript, os mesmos formulários funcionam com redirect + flash.
 */
class EnrollmentsController extends Controller
{
    /**
     * POST /admin/classes/{class_id}/enrollments   (campo "user_id")
     */
    public function create(Request $request): void
    {
        $trainingClass = $this->findClass($request);
        if ($trainingClass === null) {
            $this->respond($request, 404, false, 'Turma não encontrada.');
            return;
        }

        $student = $this->findUser($request->getParam('user_id'));
        if ($student === null) {
            $this->respond($request, 422, false, 'Selecione um aluno válido para matricular.', $trainingClass);
            return;
        }

        $enrollment = $trainingClass->enroll($student);
        if ($enrollment->hasErrors()) {
            $this->respond(
                $request,
                422,
                false,
                'Não foi possível matricular ' . $student->name . ': ' . $enrollment->firstError(),
                $trainingClass,
                $student,
                $enrollment->getErrors()
            );
            return;
        }

        $this->respond(
            $request,
            201,
            true,
            'Aluno ' . $student->name . ' matriculado com sucesso na turma ' . $trainingClass->name . '!',
            $trainingClass,
            $student
        );
    }

    /**
     * DELETE /admin/classes/{class_id}/enrollments/{user_id}
     * Remove apenas a relação: a turma e o aluno continuam existindo.
     */
    public function destroy(Request $request): void
    {
        $trainingClass = $this->findClass($request);
        if ($trainingClass === null) {
            $this->respond($request, 404, false, 'Turma não encontrada.');
            return;
        }

        $student = $this->findUser($request->getParam('user_id'));
        if ($student === null || !$trainingClass->hasStudent($student)) {
            $this->respond($request, 404, false, 'O aluno não está matriculado nesta turma.', $trainingClass);
            return;
        }

        if (!$trainingClass->unenroll($student)) {
            $this->respond($request, 500, false, 'Não foi possível remover a matrícula. Tente novamente.', $trainingClass);
            return;
        }

        $this->respond(
            $request,
            200,
            true,
            'Aluno ' . $student->name . ' removido da turma ' . $trainingClass->name . '.',
            $trainingClass,
            $student
        );
    }

    /**
     * Requisição Ajax (Accept: application/json) recebe JSON com o status HTTP adequado;
     * requisição comum recebe flash message + redirect para a página da turma.
     *
     * @param array<string, string> $errors
     */
    private function respond(
        Request $request,
        int $status,
        bool $success,
        string $message,
        ?TrainingClass $trainingClass = null,
        ?User $student = null,
        array $errors = []
    ): void {
        if ($request->acceptJson()) {
            http_response_code($status);
            $availableStudents = $trainingClass !== null ? $trainingClass->availableStudents() : [];

            $this->renderJson(
                'admin/enrollments/result',
                compact('success', 'message', 'errors', 'trainingClass', 'student', 'availableStudents')
            );
            return;
        }

        if ($success) {
            FlashMessage::success($message);
        } else {
            FlashMessage::danger($message);
        }

        $this->redirectTo(
            $trainingClass !== null
                ? route('admin.classes.show', ['id' => $trainingClass->id])
                : route('admin.classes.index')
        );
    }

    private function findClass(Request $request): ?TrainingClass
    {
        $id = $request->getParam('class_id');

        return is_numeric($id) ? TrainingClass::findById((int) $id) : null;
    }

    private function findUser(mixed $id): ?User
    {
        return is_numeric($id) ? User::findById((int) $id) : null;
    }
}
