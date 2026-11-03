<?php

namespace App\Controllers;

use App\Models\Court;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

/**
 * Galeria pública (para usuários autenticados) das fotos de cada quadra.
 */
class CourtsController extends Controller
{
    /**
     * GET /courts/{id}
     */
    public function show(Request $request): void
    {
        $id = $request->getParam('id');
        $court = is_numeric($id) ? Court::findById((int) $id) : null;

        if ($court === null) {
            FlashMessage::danger('Quadra não encontrada.');
            $this->redirectTo(route('dashboard'));
            return;
        }

        $title = 'Galeria - ' . $court->name;
        $user = $this->currentUser();
        $gallery = $court->gallery();
        $images = $gallery->images();

        $this->render('courts/show', compact('title', 'user', 'court', 'gallery', 'images'));
    }
}
