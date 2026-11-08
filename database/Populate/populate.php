<?php

require __DIR__ . '/../../config/bootstrap.php';

use App\Services\FileStorage;
use Core\Database\Database;
use Database\Populate\Seeder;

Database::migrate();

// O schema recria as tabelas; apaga os uploads antigos para não deixar arquivos órfãos no filesystem
$storage = new FileStorage();
$storage->deleteDirectory('courts');
$storage->deleteDirectory('users');

Seeder::run();
echo "Database populated successfully!\n";
echo "==========================================\n";
echo "Admin: admin@arena.com / admin123\n";
echo "Aluno: aluno@arena.com / aluno123\n";
echo "Aluno Bloqueado: bloqueado@arena.com / aluno123\n";
echo "------------------------------------------\n";
echo "Galerias: /admin/courts  |  Perfil: /profile\n";
echo "==========================================\n";
