<?php
declare(strict_types=1);

use App\Contracts\Repositories\FrequencyRepository;
use App\Services\FrequencyService;

final class InMemoryFrequencyRepository implements FrequencyRepository
{
    public function classesForActor(string $role, int $professorId): array
    {
        return [['id' => 1, 'nome' => $role . ':' . $professorId]];
    }

    public function dailyRows(string $date, ?int $classId, string $studentName, string $role, int $professorId): array
    {
        return [
            ['id' => 1, 'nome' => 'Ana', 'ultima_entrada' => '2026-06-26 07:00:00', 'ultima_saida' => null, 'aviso' => null],
            ['id' => 2, 'nome' => 'Bia', 'ultima_entrada' => null, 'ultima_saida' => null, 'aviso' => null],
            ['id' => 3, 'nome' => 'Caio', 'ultima_entrada' => '2026-06-26 07:00:00', 'ultima_saida' => '2026-06-26 11:30:00', 'aviso' => null],
            ['id' => 4, 'nome' => 'Davi', 'ultima_entrada' => null, 'ultima_saida' => null, 'aviso' => 'enviado|Doença'],
        ];
    }
}

return static function (): void {
    $service = new FrequencyService(new InMemoryFrequencyRepository());
    $rows = $service->dailyReport('2026-06-26', 1, '', 'professor', 9);

    if (($rows[0]['situacao'] ?? null) !== 'presente') throw new RuntimeException('Entrada não marcou presente.');
    if (($rows[1]['situacao'] ?? null) !== 'ausente') throw new RuntimeException('Sem registro não marcou ausente.');
    if (($rows[2]['situacao'] ?? null) !== 'saiu') throw new RuntimeException('Saída posterior não marcou saiu.');
    if (($rows[3]['situacao'] ?? null) !== 'com aviso de falta') throw new RuntimeException('Aviso não teve prioridade.');

    $onlyAbsent = $service->dailyReport('2026-06-26', 1, '', 'professor', 9, 'ausente');
    if (count($onlyAbsent) !== 1 || ($onlyAbsent[0]['nome'] ?? null) !== 'Bia') throw new RuntimeException('Filtro de situação falhou.');
};
