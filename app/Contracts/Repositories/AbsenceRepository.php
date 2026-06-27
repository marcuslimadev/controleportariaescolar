<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface AbsenceRepository
{
    public function childrenForGuardian(int $guardianId): array;

    public function create(array $data): int;

    public function updateStatus(int $id, string $status, int $reviewedBy): void;

    public function listForAdmin(?string $status = null): array;

    public function listForTeacher(int $professorId): array;
}
