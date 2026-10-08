<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $title = 'UTFPR Arena Beach Tennis';
        $user = $this->currentUser();
        $this->render('home/index', compact('title', 'user'));
    }
}
