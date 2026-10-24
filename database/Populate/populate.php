<?php

require __DIR__ . '/../../config/bootstrap.php';

use App\Models\User;
use Core\Database\Database;

Database::migrate();

// 1. Administrador (Gestor da Arena)
$admin = new User([
    'name' => 'Administrador Arena',
    'email' => 'admin@arena.com',
    'password' => 'admin123',
    'password_confirmation' => 'admin123',
    'role' => User::ROLE_MANAGER,
    'status' => User::STATUS_ACTIVE,
    'phone' => '(41) 99999-0001',
    'cpf' => '000.000.000-01'
]);
$admin->save();

// 2. Aluno Ativo (Regular)
$aluno = new User([
    'name' => 'Aluno Beach Tennis',
    'email' => 'aluno@arena.com',
    'password' => 'aluno123',
    'password_confirmation' => 'aluno123',
    'role' => User::ROLE_STUDENT,
    'status' => User::STATUS_ACTIVE,
    'phone' => '(41) 98888-0002',
    'cpf' => '111.111.111-02'
]);
$aluno->save();

// 3. Aluno Inadimplente (Bloqueado)
$bloqueado = new User([
    'name' => 'Aluno Inadimplente',
    'email' => 'bloqueado@arena.com',
    'password' => 'aluno123',
    'password_confirmation' => 'aluno123',
    'role' => User::ROLE_STUDENT,
    'status' => User::STATUS_BLOCKED,
    'phone' => '(41) 97777-0003',
    'cpf' => '222.222.222-03'
]);
$bloqueado->save();

// 4. Outros atletas para teste de paginação e gestão
$aluno3 = new User([
    'name' => 'Beatriz Lima',
    'email' => 'beatriz@arena.com',
    'password' => 'aluno123',
    'password_confirmation' => 'aluno123',
    'role' => User::ROLE_STUDENT,
    'status' => User::STATUS_ACTIVE,
    'phone' => '(41) 96666-0004',
    'cpf' => '333.333.333-04'
]);
$aluno3->save();

$aluno4 = new User([
    'name' => 'Carlos Eduardo',
    'email' => 'carlos@arena.com',
    'password' => 'aluno123',
    'password_confirmation' => 'aluno123',
    'role' => User::ROLE_STUDENT,
    'status' => User::STATUS_ACTIVE,
    'phone' => '(41) 95555-0005',
    'cpf' => '444.444.444-05'
]);
$aluno4->save();

$aluno5 = new User([
    'name' => 'Daniela Souza',
    'email' => 'daniela@arena.com',
    'password' => 'aluno123',
    'password_confirmation' => 'aluno123',
    'role' => User::ROLE_STUDENT,
    'status' => User::STATUS_BLOCKED,
    'phone' => '(41) 94444-0006',
    'cpf' => '555.555.555-06'
]);
$aluno5->save();

$aluno6 = new User([
    'name' => 'Eduardo Mendes',
    'email' => 'eduardo@arena.com',
    'password' => 'aluno123',
    'password_confirmation' => 'aluno123',
    'role' => User::ROLE_STUDENT,
    'status' => User::STATUS_ACTIVE,
    'phone' => '(41) 93333-0007',
    'cpf' => '666.666.666-07'
]);
$aluno6->save();

// 4. Inserir Quadras da Arena de Beach Tennis
$pdo = Database::getDatabaseConn();
$pdo->exec("INSERT INTO courts (name, type, status) VALUES 
    ('Quadra Central (Areia Premium)', 'beach_tennis', 'available'),
    ('Quadra 02 (Areia Branca)', 'beach_tennis', 'available'),
    ('Quadra 03 (Treino e Aulas)', 'beach_tennis', 'maintenance');
");

// 5. Inserir Faturas de Exemplo para o Aluno
if ($aluno->id !== null) {
    $stmt = $pdo->prepare("INSERT INTO invoices (user_id, amount, due_date, status) VALUES 
        (:uid, 180.00, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'paid'),
        (:uid, 180.00, DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'pending');
    ");
    $stmt->execute([':uid' => $aluno->id]);
}

// 6. Inserir Faturas Atrasadas para o Aluno Bloqueado (2 meses em atraso)
if ($bloqueado->id !== null) {
    $stmt = $pdo->prepare("INSERT INTO invoices (user_id, amount, due_date, status) VALUES 
        (:uid, 180.00, DATE_SUB(CURDATE(), INTERVAL 65 DAY), 'overdue'),
        (:uid, 180.00, DATE_SUB(CURDATE(), INTERVAL 35 DAY), 'overdue');
    ");
    $stmt->execute([':uid' => $bloqueado->id]);
}

echo "Database populated successfully!\n";
echo "==========================================\n";
echo "Admin: admin@arena.com / admin123\n";
echo "Aluno: aluno@arena.com / aluno123\n";
echo "Aluno Bloqueado: bloqueado@arena.com / aluno123\n";
echo "==========================================\n";
