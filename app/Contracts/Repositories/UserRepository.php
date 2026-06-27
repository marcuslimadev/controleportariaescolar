<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface UserRepository
{
    public function findActiveByEmail(string $email): ?array;

    public function findActiveById(int $id): ?array;

    public function updatePasswordHash(int $id, string $hash): void;

    public function updatePhoto(int $id, string $photoUrl): void;
}
