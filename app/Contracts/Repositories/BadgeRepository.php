<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface BadgeRepository
{
    public function findGuardianByQrToken(string $token): ?array;

    public function findGuardianByApprovedInvite(string $inviteToken, int $guardianId): ?array;

    public function findActiveGuardianById(int $id): ?array;

    public function findGuardianById(int $id): ?array;

    public function updateGuardianQrToken(int $guardianId, string $token): void;

    public function withdrawalChildren(int $guardianId): array;

    public function recordGuardianIssue(int $guardianId, int $issuedBy, string $token): void;

    public function findActiveStudentSecurityBadge(int $studentId): ?array;
}
