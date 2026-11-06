<?php

namespace App\Controllers\Admin;

use App\Models\Court;
use App\Models\CourtImage;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

/**
 * Upload e remoção das imagens da galeria de uma quadra (recurso aninhado: /admin/courts/{court_id}/images).
 */
class CourtImagesController extends Controller
{
    /**
     * POST /admin/courts/{court_id}/images  (multipart/form-data, campo "image")
     */
    public function create(Request $request): void
    {
        $courtId = $request->getParam('court_id');
        $court = is_numeric($courtId) ? Court::findById((int) $courtId) : null;

        // Integridade: não é possível registrar imagem em uma galeria inexistente
        if ($court === null) {
            FlashMessage::danger('Galeria inexistente: a quadra #' . (string) $courtId . ' não existe.');
            $this->redirectTo(route('admin.courts.index'));
            return;
        }

        $gallery = $court->gallery();
        $caption = $request->getParam('caption');
        $image = $gallery->add($request->getFile('image'), is_string($caption) ? $caption : null);

        if ($image === null) {
            FlashMessage::danger('Erro ao enviar imagem: ' . (string) $gallery->firstError());
            $this->redirectTo(route('admin.courts.show', ['id' => $court->id]));
            return;
        }

        FlashMessage::success(
            'Imagem ' . $image->original_name . ' enviada com sucesso! Arquivo salvo em: '
            . $gallery->url($image)
        );
        $this->redirectTo(route('admin.courts.show', ['id' => $court->id]));
    }

    /**
     * DELETE /admin/courts/{court_id}/images/{id}
     */
    public function destroy(Request $request): void
    {
        $courtId = $request->getParam('court_id');
        $court = is_numeric($courtId) ? Court::findById((int) $courtId) : null;

        if ($court === null) {
            FlashMessage::danger('Galeria inexistente: a quadra informada não existe.');
            $this->redirectTo(route('admin.courts.index'));
            return;
        }

        $imageId = $request->getParam('id');

        // HasMany::findById garante que a imagem pertence a ESTA quadra
        /** @var CourtImage|null $image */
        $image = is_numeric($imageId) ? $court->images()->findById((int) $imageId) : null;

        if ($image === null) {
            FlashMessage::danger('Imagem não encontrada nesta galeria.');
            $this->redirectTo(route('admin.courts.show', ['id' => $court->id]));
            return;
        }

        $gallery = $court->gallery();
        $removedPath = $gallery->url($image);
        $gallery->remove($image);

        FlashMessage::success(
            'Imagem ' . $image->original_name . ' removida com sucesso! Arquivo apagado do filesystem: '
            . $removedPath
        );
        $this->redirectTo(route('admin.courts.show', ['id' => $court->id]));
    }
}
