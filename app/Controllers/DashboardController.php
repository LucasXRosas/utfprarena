<?php

namespace App\Controllers;

use App\Models\Court;
use App\Models\Invoice;
use Core\Http\Controllers\Controller;
use Core\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->currentUser();
        $title = 'Área do Aluno - Arena Beach Tennis';

        // Buscar dados utilizando a camada de Model (MVC)
        $courts = Court::all();
        $invoices = ($user !== null && $user->id !== null)
            ? Invoice::allByUser($user->id)
            : [];

        $this->render('dashboard/index', compact('title', 'user', 'courts', 'invoices'));
    }
}
