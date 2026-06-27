<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\GuardianPortalRepository;

final class GuardianPortalService
{
    public function __construct(private GuardianPortalRepository $portal) {}

    public function dashboard(int $guardianId, string $from, string $to): array
    {
        [$from, $to] = $this->normalizePeriod($from, $to);
        return [
            'from' => $from,
            'to' => $to,
            'children' => $this->portal->children($guardianId),
            'movements' => $this->portal->movements($guardianId, $from, $to),
        ];
    }

    public function absences(int $guardianId): array
    {
        return $this->portal->absences($guardianId);
    }

    private function normalizePeriod(string $from, string $to): array
    {
        $today = date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = $today;
        if ($from > $to) [$from, $to] = [$to, $from];
        return [$from, $to];
    }
}
