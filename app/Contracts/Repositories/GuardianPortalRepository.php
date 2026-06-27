<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface GuardianPortalRepository
{
    public function children(int $guardianId): array;
    public function movements(int $guardianId, string $from, string $to): array;
    public function absences(int $guardianId): array;
}
