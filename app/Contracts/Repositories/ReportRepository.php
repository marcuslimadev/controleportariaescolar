<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface ReportRepository
{
    public function accessMovements(string $from, string $to): array;

    public function dashboardSummary(string $date): array;
}
