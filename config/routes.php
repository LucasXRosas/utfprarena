<?php

use App\Controllers\Admin\UsersController as AdminUsersController;
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
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Rotas Administrativas e CRUD RESTful (protegidas por 'auth' e 'admin')
Route::middleware('auth')->group(function () {
    Route::middleware('admin')->group(function () {
        // Padrão RESTful para recurso 'users' (Admin)
        Route::get('/admin', [AdminUsersController::class, 'index'])->name('admin.index');
        Route::get('/admin/users', [AdminUsersController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/new', [AdminUsersController::class, 'new'])->name('admin.users.new');
        Route::post('/admin/users', [AdminUsersController::class, 'create'])->name('admin.users.create');
        Route::get('/admin/users/{id}', [AdminUsersController::class, 'show'])->name('admin.users.show');
        Route::get('/admin/users/{id}/edit', [AdminUsersController::class, 'edit'])->name('admin.users.edit');
        Route::put('/admin/users/{id}', [AdminUsersController::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/users/{id}', [AdminUsersController::class, 'destroy'])->name('admin.users.destroy');

        // Ações de controle de acesso (Catraca lógica)
        Route::post('/admin/users/{id}/block', [AdminUsersController::class, 'block'])->name('admin.users.block');
        Route::post('/admin/users/{id}/activate', [AdminUsersController::class, 'activate'])->name('admin.users.activate');

        // Aliases legados para compatibilidade
        Route::post('/admin/block/{id}', [AdminController::class, 'blockUser'])->name('admin.blockUser');
        Route::post('/admin/activate/{id}', [AdminController::class, 'activateUser'])->name('admin.activateUser');
    });
});
