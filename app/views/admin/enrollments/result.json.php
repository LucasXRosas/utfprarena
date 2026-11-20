<?php

/**
 * Resposta JSON das ações de matrícula (renderizada por Controller::renderJson()).
 * O framework faz extract() das variáveis do controller, inclui este arquivo
 * e, no fim, executa echo json_encode($json).
 *
 * @var bool $success
 * @var string $message
 * @var array<string, string> $errors
 * @var ?\App\Models\TrainingClass $trainingClass
 * @var ?\App\Models\User $student
 * @var array<\App\Models\User> $availableStudents
 */

$json['success'] = $success;
$json['message'] = $message;
$json['errors'] = (object) $errors;

$json['student'] = $student === null ? null : [
    'id' => $student->id,
    'name' => $student->name,
    'email' => $student->email,
    'status' => $student->status,
];

if ($trainingClass !== null) {
    $studentsCount = $trainingClass->studentsCount();

    $json['training_class'] = [
        'id' => $trainingClass->id,
        'name' => $trainingClass->name,
        'capacity' => (int) $trainingClass->capacity,
        'students_count' => $studentsCount,
        'is_full' => $studentsCount >= (int) $trainingClass->capacity,
    ];
}

$json['available_students'] = array_map(fn($user) => [
    'id' => $user->id,
    'name' => $user->name,
    'email' => $user->email,
], $availableStudents);
