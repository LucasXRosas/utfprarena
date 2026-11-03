<?php

namespace Tests\Support\Upload;

use App\Services\FileStorage;

/**
 * Em testes (CLI) não existe upload HTTP real, então move_uploaded_file() sempre falharia.
 * Este storage substitui apenas a etapa de "mover" por uma cópia comum.
 */
class TestFileStorage extends FileStorage
{
    protected function moveUploadedFile(string $from, string $to): bool
    {
        return copy($from, $to);
    }
}
