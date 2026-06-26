<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface GuardianRepository
{
    public function findActiveIdByQrToken(string $token): ?int;

    public function findActiveByQrToken(string $token): ?array;

    public function authorizedChildrenForWithdrawal(int $guardianId): array;

    public function canWithdrawStudent(int $guardianId, int $studentId): bool;

    public function findIdByCpf(string $cpf): ?int;

    public function findActiveByCpfOrPhone(string $digits): ?array;

    public function updatePasswordHash(int $id, string $hash): void;

    public function createFromInvite(array $invite): int;

    public function updateFromInvite(int $guardianId, array $invite): void;
}
