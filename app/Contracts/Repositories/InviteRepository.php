<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface InviteRepository
{
    public function create(string $phone, string $tokenHash, int $createdBy): int;

    public function expireOld(): void;

    public function pendingList(int $limit = 30): array;

    public function findReadyForApproval(int $id): ?array;

    public function markApproved(int $id, int $approvedBy, int $guardianId, int $studentId): void;
}
