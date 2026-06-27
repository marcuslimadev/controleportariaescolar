<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\ReportRepository;

final class ReportService
{
    public function __construct(private ReportRepository $reports) {}

    public function accessMovements(string $from, string $to): array
    {
        [$from, $to] = $this->period($from, $to);
        $rows = $this->reports->accessMovements($from, $to);
        return ['from' => $from, 'to' => $to, 'rows' => $rows, 'summary' => $this->summarizeAccessRows($rows)];
    }

    private function summarizeAccessRows(array $rows): array
    {
        $students = [];
        $summary = ['total' => 0, 'entradas' => 0, 'saidas' => 0, 'alunos' => 0];
        foreach ($rows as $row) {
            $summary['total']++;
            if (($row['tipo'] ?? '') === 'saida') {
                $summary['saidas']++;
            } else {
                $summary['entradas']++;
            }
            $studentKey = (string)($row['aluno_id'] ?? $row['aluno'] ?? '');
            if ($studentKey !== '') {
                $students[$studentKey] = true;
            }
        }
        $summary['alunos'] = count($students);
        return $summary;
    }

    private function period(string $from, string $to): array
    {
        $today = date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = $today;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = $today;
        if ($from > $to) [$from, $to] = [$to, $from];
        return [$from, $to];
    }
}
