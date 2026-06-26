<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface UserRepository
{
    public function findActiveByEmail(string $email): ?array;

    public function updatePasswordHash(int $id, string $hash): void;
}
