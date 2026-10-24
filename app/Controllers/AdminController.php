<?php

namespace App\Controllers;

use App\Controllers\Admin\UsersController as AdminUsersController;
use Core\Http\Request;

class AdminController extends AdminUsersController
{
    /**
     * Alias para compatibilidade com rotas legadas
     */
    public function blockUser(Request $request): void
    {
        $this->block($request);
    }

    /**
     * Alias para compatibilidade com rotas legadas
     */
    public function activateUser(Request $request): void
    {
        $this->activate($request);
    }
}
