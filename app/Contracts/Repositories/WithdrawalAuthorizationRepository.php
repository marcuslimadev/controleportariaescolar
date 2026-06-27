<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface WithdrawalAuthorizationRepository
{
    public function childrenForGuardian(int $guardianId): array;

    public function guardianCanAuthorize(int $guardianId, int $studentId): bool;

    public function create(array $data): int;

    public function listForGuardian(int $guardianId): array;

    public function activeForGate(): array;

    public function updateStatus(int $id, string $status, ?int $operatorId = null): void;

    public function cancelForGuardian(int $id, int $guardianId): void;
}
