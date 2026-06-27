<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface EmergencyBadgeRepository
{
    public function findActiveStudentByToken(string $token): ?array;
    public function guardianCanConsult(int $studentId, int $guardianId): bool;
    public function createAlert(array $data): int;
}
