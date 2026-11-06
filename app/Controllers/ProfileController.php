<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

/**
 * Aba "Meu Perfil": qualquer usuário autenticado pode enviar, visualizar e remover sua foto de perfil.
 */
class ProfileController extends Controller
{
    /**
     * GET /profile
     */
    public function show(Request $request): void
    {
        $user = $this->currentUser();
        if ($user === null) {
            $this->redirectTo(route('users.login'));
            return;
        }

        $title = 'Meu Perfil - UTFPR Arena';
        $avatar = $user->avatar();
        $validator = $avatar->validator();

        $this->render('profile/show', compact('title', 'user', 'avatar', 'validator'));
    }

    /**
     * POST /profile/avatar  (multipart/form-data, campo "avatar")
     */
    public function updateAvatar(Request $request): void
    {
        $user = $this->currentUser();
        if ($user === null) {
            $this->redirectTo(route('users.login'));
            return;
        }

        $avatar = $user->avatar();

        if (!$avatar->update($request->getFile('avatar'))) {
            FlashMessage::danger('Erro ao enviar foto: ' . (string) $avatar->firstError());
            $this->redirectTo(route('profile.show'));
            return;
        }

        FlashMessage::success('Foto de perfil atualizada com sucesso! Arquivo salvo em: ' . $avatar->path());
        $this->redirectTo(route('profile.show'));
    }

    /**
     * DELETE /profile/avatar
     */
    public function destroyAvatar(Request $request): void
    {
        $user = $this->currentUser();
        if ($user === null) {
            $this->redirectTo(route('users.login'));
            return;
        }

        $avatar = $user->avatar();
        $removedPath = $avatar->path();

        if (!$avatar->remove()) {
            FlashMessage::danger((string) $avatar->firstError());
            $this->redirectTo(route('profile.show'));
            return;
        }

        FlashMessage::success('Foto de perfil removida com sucesso! Arquivo apagado do filesystem: ' . $removedPath);
        $this->redirectTo(route('profile.show'));
    }
}
