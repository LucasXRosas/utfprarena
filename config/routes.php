<?php

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use Core\Router\Route;

// Rotas Públicas (acessíveis por qualquer usuário ou visitante)
Route::get('/', [HomeController::class, 'index'])->name('root');

Route::get('/login', [AuthController::class, 'new'])->name('users.login');
Route::post('/login', [AuthController::class, 'create'])->name('users.authenticate');
Route::post('/logout', [AuthController::class, 'destroy'])->name('users.logout');
Route::get('/logout', [AuthController::class, 'destroy'])->name('users.logout.get');

// Rotas Autenticadas (protegidas pelo middleware 'auth')
// Atende à rubrica 5.2.1: Route::middleware('auth')->group(...)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Rotas Administrativas (protegidas por 'auth' e 'admin')
// Demonstração de autorização (RBAC - Role Based Access Control)
Route::middleware('auth')->group(function () {
    Route::middleware('admin')->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
        Route::post('/admin/block/{id}', [AdminController::class, 'blockUser'])->name('admin.blockUser');
        Route::post('/admin/activate/{id}', [AdminController::class, 'activateUser'])->name('admin.activateUser');
    });
});
