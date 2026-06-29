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
        $children = $this->portal->children($guardianId);
        $movements = $this->portal->movements($guardianId, $from, $to);
        return [
            'from' => $from,
            'to' => $to,
            'children' => $children,
            'movements' => $movements,
            'summary' => $this->summarize($children, $movements),
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

    private function summarize(array $children, array $movements): array
    {
        $summary = ['children' => count($children), 'movements' => 0, 'entradas' => 0, 'saidas' => 0, 'dentro' => 0];
        foreach ($children as $child) {
            if (($child['ultimo_tipo'] ?? '') === 'entrada') $summary['dentro']++;
        }
        foreach ($movements as $movement) {
            if (empty($movement['tipo'])) continue;
            $summary['movements']++;
            if ($movement['tipo'] === 'saida') $summary['saidas']++;
            else $summary['entradas']++;
        }
        return $summary;
    }
}
