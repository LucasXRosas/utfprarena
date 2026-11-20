<?php

namespace App\Controllers\Admin;

use App\Models\TrainingClass;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

/**
 * Gestão das turmas de treino (um dos lados da relação NxN Turma <-> Aluno).
 * As matrículas (tabela pivô) são feitas via Ajax pelo EnrollmentsController.
 */
class TrainingClassesController extends Controller
{
    /**
     * GET /admin/classes
     */
    public function index(Request $request): void
    {
        $title = 'Turmas - Painel Gerencial';
        $classes = TrainingClass::allOrdered();

        $this->render('admin/classes/index', compact('title', 'classes'));
    }

    /**
     * GET /admin/classes/new
     */
    public function new(Request $request): void
    {
        $title = 'Cadastrar Turma - Painel Gerencial';
        $trainingClass = new TrainingClass();

        $this->render('admin/classes/new', compact('title', 'trainingClass'));
    }

    /**
     * POST /admin/classes
     */
    public function create(Request $request): void
    {
        $trainingClass = new TrainingClass([
            'name' => trim((string) $request->getParam('training_class.name', '')),
            'level' => (string) $request->getParam('training_class.level', TrainingClass::LEVEL_BEGINNER),
            'schedule' => trim((string) $request->getParam('training_class.schedule', '')),
            'capacity' => trim((string) $request->getParam('training_class.capacity', '')),
        ]);

        if (!$trainingClass->save()) {
            http_response_code(422);
            $title = 'Cadastrar Turma - Painel Gerencial';
            $this->render('admin/classes/new', compact('title', 'trainingClass'));
            return;
        }

        FlashMessage::success('Turma ' . $trainingClass->name . ' cadastrada com sucesso! Matricule os alunos.');
        $this->redirectTo(route('admin.classes.show', ['id' => $trainingClass->id]));
    }

    /**
     * GET /admin/classes/{id} — alunos matriculados + matrícula/remoção via Ajax
     */
    public function show(Request $request): void
    {
        $trainingClass = $this->findClass($request);
        if ($trainingClass === null) {
            return;
        }

        $title = 'Turma: ' . $trainingClass->name;
        $students = $trainingClass->students;
        $availableStudents = $trainingClass->availableStudents();

        $this->render('admin/classes/show', compact('title', 'trainingClass', 'students', 'availableStudents'));
    }

    /**
     * DELETE /admin/classes/{id}
     * Só exclui turmas sem alunos matriculados (registro com dependências é bloqueado).
     */
    public function destroy(Request $request): void
    {
        $trainingClass = $this->findClass($request);
        if ($trainingClass === null) {
            return;
        }

        if (!$trainingClass->destroy()) {
            FlashMessage::danger((string) $trainingClass->firstError());
            $this->redirectTo(route('admin.classes.index'));
            return;
        }

        FlashMessage::success('Turma ' . $trainingClass->name . ' excluída com sucesso!');
        $this->redirectTo(route('admin.classes.index'));
    }

    private function findClass(Request $request): ?TrainingClass
    {
        $id = $request->getParam('id');
        $trainingClass = is_numeric($id) ? TrainingClass::findById((int) $id) : null;

        if ($trainingClass === null) {
            FlashMessage::danger('Turma não encontrada.');
            $this->redirectTo(route('admin.classes.index'));
            return null;
        }

        return $trainingClass;
    }
}
