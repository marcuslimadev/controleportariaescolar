<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface InviteRepository
{
    public function create(string $phone, string $tokenHash, int $createdBy): int;

    public function expireOld(): void;

    public function pendingList(int $limit = 30): array;

    public function pendingSummary(): array;

    public function approvalPreview(int $id): ?array;

    public function findByPublicToken(string $token): ?array;

    public function expire(int $id): void;

    public function fillByFamily(int $id, array $data): void;

    public function findReadyForApproval(int $id): ?array;

    public function markApproved(int $id, int $approvedBy, int $guardianId, int $studentId): void;
}
