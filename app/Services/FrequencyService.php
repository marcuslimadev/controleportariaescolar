<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\FrequencyRepository;

final class FrequencyService
{
    public const SITUATIONS = ['presente','ausente','com aviso de falta','saiu','sem registro'];

    public function __construct(private FrequencyRepository $frequency) {}

    public function classesForActor(string $role, int $professorId): array
    {
        return $this->frequency->classesForActor($role, $professorId);
    }

    public function dailyReport(string $date, ?int $classId, string $studentName, string $role, int $professorId, string $situation = ''): array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $studentName = trim($studentName);
        $situation = in_array($situation, self::SITUATIONS, true) ? $situation : '';

        $rows = [];
        foreach ($this->frequency->dailyRows($date, $classId, $studentName, $role, $professorId) as $row) {
            $normalized = $this->normalizeRow($row);
            if ($situation !== '' && $normalized['situacao'] !== $situation) {
                continue;
            }
            $rows[] = $normalized;
        }

        return $rows;
    }

    public function normalizeRow(array $row): array
    {
        $entry = (string)($row['ultima_entrada'] ?? '');
        $exit = (string)($row['ultima_saida'] ?? '');
        $notice = (string)($row['aviso'] ?? '');

        $situation = 'sem registro';
        if ($entry !== '') {
            $situation = 'presente';
        }
        if ($exit !== '' && ($entry === '' || strtotime($exit) > strtotime($entry))) {
            $situation = 'saiu';
        }
        if ($entry === '' && $exit === '') {
            $situation = 'ausente';
        }

        $noticeParts = $notice !== '' ? explode('|', $notice, 2) : [];
        if ($noticeParts) {
            $situation = 'com aviso de falta';
        }

        $row['situacao'] = $situation;
        $row['aviso_texto'] = $noticeParts ? (($noticeParts[0] ?? '') . ' - ' . ($noticeParts[1] ?? '')) : '';

        return $row;
    }
}
