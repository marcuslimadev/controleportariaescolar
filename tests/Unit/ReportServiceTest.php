<?php
declare(strict_types=1);

use App\Contracts\Repositories\ReportRepository;
use App\Services\ReportService;

final class InMemoryReportRepository implements ReportRepository
{
    public array $lastPeriod = [];

    public function accessMovements(string $from, string $to): array
    {
        $this->lastPeriod = compact('from', 'to');
        return [['aluno' => 'Aluno', 'tipo' => 'entrada']];
    }
}

return static function (): void {
    $repo = new InMemoryReportRepository();
    $service = new ReportService($repo);
    $report = $service->accessMovements('2026-06-30', '2026-06-01');

    if (($report['from'] ?? null) !== '2026-06-01') throw new RuntimeException('Período do relatório não foi normalizado.');
    if (count($report['rows'] ?? []) !== 1) throw new RuntimeException('Relatório não retornou linhas.');
};
