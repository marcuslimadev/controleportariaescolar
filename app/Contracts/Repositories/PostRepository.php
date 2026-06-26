<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface PostRepository
{
    public function findActiveById(int $id): ?array;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function softDelete(int $id): void;
}
