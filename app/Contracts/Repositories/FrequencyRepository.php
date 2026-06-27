<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface FrequencyRepository
{
    public function classesForActor(string $role, int $professorId): array;

    public function dailyRows(string $date, ?int $classId, string $studentName, string $role, int $professorId): array;
}
