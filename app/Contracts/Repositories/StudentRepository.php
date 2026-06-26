<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface StudentRepository
{
    public function activeExistsByQrToken(string $token): bool;

    public function createFromInvite(array $invite, string $qrToken): int;

    public function linkGuardian(int $studentId, int $guardianId): void;
}
