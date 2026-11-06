<?php

use App\Controllers\Admin\CourtImagesController as AdminCourtImagesController;
use App\Controllers\Admin\CourtsController as AdminCourtsController;
use App\Controllers\Admin\UsersController as AdminUsersController;
use App\Controllers\AuthController;
use App\Controllers\CourtsController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\ProfileController;
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

    // Aba "Meu Perfil": upload, visualização e remoção da foto de perfil (avatar)
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');

    // Galeria de fotos de uma quadra (visualização para alunos)
    Route::get('/courts/{id}', [CourtsController::class, 'show'])->name('courts.show');
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

        // Quadras (entidade principal) e sua galeria de imagens (relação 1xN)
        Route::get('/admin/courts', [AdminCourtsController::class, 'index'])->name('admin.courts.index');
        Route::get('/admin/courts/new', [AdminCourtsController::class, 'new'])->name('admin.courts.new');
        Route::post('/admin/courts', [AdminCourtsController::class, 'create'])->name('admin.courts.create');
        Route::get('/admin/courts/{id}', [AdminCourtsController::class, 'show'])->name('admin.courts.show');
        Route::delete('/admin/courts/{id}', [AdminCourtsController::class, 'destroy'])->name('admin.courts.destroy');

        Route::post('/admin/courts/{court_id}/images', [AdminCourtImagesController::class, 'create'])
            ->name('admin.courts.images.create');
        Route::delete('/admin/courts/{court_id}/images/{id}', [AdminCourtImagesController::class, 'destroy'])
            ->name('admin.courts.images.destroy');
    });
});
