<?php
declare(strict_types=1);

use App\Contracts\Repositories\GuardianPortalRepository;
use App\Services\GuardianPortalService;

final class InMemoryGuardianPortalRepository implements GuardianPortalRepository
{
    public array $lastPeriod = [];

    public function children(int $guardianId): array
    {
        return [['nome' => 'Aluno', 'turma' => '1A']];
    }

    public function movements(int $guardianId, string $from, string $to): array
    {
        $this->lastPeriod = compact('guardianId', 'from', 'to');
        return [['aluno' => 'Aluno', 'tipo' => 'entrada']];
    }

    public function absences(int $guardianId): array
    {
        return [['responsavel_id' => $guardianId]];
    }
}

return static function (): void {
    $repo = new InMemoryGuardianPortalRepository();
    $service = new GuardianPortalService($repo);
    $dashboard = $service->dashboard(7, '2026-06-30', '2026-06-01');

    if (($dashboard['from'] ?? null) !== '2026-06-01') throw new RuntimeException('Período do responsável não foi normalizado.');
    if (($repo->lastPeriod['guardianId'] ?? null) !== 7) throw new RuntimeException('Responsável não foi repassado ao portal.');
    if (count($dashboard['children'] ?? []) !== 1) throw new RuntimeException('Filhos do responsável não retornaram.');
    if (($service->absences(7)[0]['responsavel_id'] ?? null) !== 7) throw new RuntimeException('Faltas do responsável não retornaram.');
};
