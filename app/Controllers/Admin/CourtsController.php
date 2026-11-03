<?php

namespace App\Controllers\Admin;

use App\Models\Court;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

/**
 * Gestão das quadras (entidade principal da relação 1xN com as imagens).
 */
class CourtsController extends Controller
{
    /**
     * GET /admin/courts
     */
    public function index(Request $request): void
    {
        $title = 'Quadras e Galerias - Painel Gerencial';
        $courts = Court::all();

        $this->render('admin/courts/index', compact('title', 'courts'));
    }

    /**
     * GET /admin/courts/new
     */
    public function new(Request $request): void
    {
        $title = 'Cadastrar Quadra - Painel Gerencial';
        $court = new Court();

        $this->render('admin/courts/new', compact('title', 'court'));
    }

    /**
     * POST /admin/courts
     */
    public function create(Request $request): void
    {
        $name = trim((string) $request->getParam('court.name', ''));
        $status = (string) $request->getParam('court.status', Court::STATUS_AVAILABLE);

        $court = new Court([
            'name' => $name,
            'status' => in_array($status, [Court::STATUS_AVAILABLE, Court::STATUS_MAINTENANCE], true)
                ? $status
                : Court::STATUS_AVAILABLE,
        ]);

        if (!$court->save()) {
            FlashMessage::danger('Erro ao cadastrar quadra: nome ' . ($court->errors('name') ?? 'inválido'));
            $this->redirectTo(route('admin.courts.new'));
            return;
        }

        FlashMessage::success('Quadra ' . htmlspecialchars($court->name) . ' cadastrada com sucesso! Envie as fotos da galeria.');
        $this->redirectTo(route('admin.courts.show', ['id' => $court->id]));
    }

    /**
     * GET /admin/courts/{id}  — Galeria da quadra com upload/remoção de imagens
     */
    public function show(Request $request): void
    {
        $court = $this->findCourt($request);
        if ($court === null) {
            return;
        }

        $title = 'Galeria da Quadra: ' . $court->name;
        $gallery = $court->gallery();
        $images = $gallery->images();
        $validator = $gallery->validator();

        $this->render('admin/courts/show', compact('title', 'court', 'gallery', 'images', 'validator'));
    }

    /**
     * DELETE /admin/courts/{id}
     * Remove a quadra; o banco apaga as imagens em cascata e o model apaga os arquivos.
     */
    public function destroy(Request $request): void
    {
        $court = $this->findCourt($request);
        if ($court === null) {
            return;
        }

        $name = $court->name;
        $totalImages = $court->imagesCount();
        $court->destroy();

        FlashMessage::success(
            'Quadra ' . htmlspecialchars($name) . ' removida com sucesso! ' . $totalImages
            . ' imagem(ns) apagada(s) do banco (ON DELETE CASCADE) e do filesystem.'
        );
        $this->redirectTo(route('admin.courts.index'));
    }

    private function findCourt(Request $request): ?Court
    {
        $id = $request->getParam('id');
        $court = is_numeric($id) ? Court::findById((int) $id) : null;

        if ($court === null) {
            FlashMessage::danger('Quadra não encontrada.');
            $this->redirectTo(route('admin.courts.index'));
            return null;
        }

        return $court;
    }
}
