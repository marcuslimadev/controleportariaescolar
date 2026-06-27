<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface QuickRegistrationRepository
{
    public function activeClasses(): array;

    public function classExists(int $classId): bool;

    public function create(array $data, string $studentQrToken, string $guardianPasswordHash): array;
}
