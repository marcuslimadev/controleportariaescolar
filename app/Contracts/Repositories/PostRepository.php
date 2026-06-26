<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface PostRepository
{
    public function findActiveById(int $id): ?array;

    public function softDelete(int $id): void;
}
