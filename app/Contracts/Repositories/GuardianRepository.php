<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface GuardianRepository
{
    public function findActiveIdByQrToken(string $token): ?int;

    public function findActiveByQrToken(string $token): ?array;

    public function authorizedChildrenForWithdrawal(int $guardianId): array;

    public function canWithdrawStudent(int $guardianId, int $studentId): bool;
}
