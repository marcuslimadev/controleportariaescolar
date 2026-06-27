<?php
declare(strict_types=1);

use App\Contracts\Repositories\AbsenceRepository;
use App\Services\AbsenceService;

final class InMemoryAbsenceRepository implements AbsenceRepository
{
    public array $created = [];
    public array $statuses = [];
    public array $children = [
        ['id' => 10, 'nome' => 'Aluno', 'turma_id' => 2, 'turma' => '1A'],
    ];

    public function childrenForGuardian(int $guardianId): array
    {
        return $guardianId === 7 ? $this->children : [];
    }

    public function create(array $data): int
    {
        $this->created[] = $data;
        return 55;
    }

    public function updateStatus(int $id, string $status, int $reviewedBy): void
    {
        $this->statuses[] = compact('id', 'status', 'reviewedBy');
    }

    public function listForAdmin(?string $status = null): array
    {
        return [['id' => 1, 'status' => $status ?: 'enviado']];
    }
}

final class AbsenceSpyAuditLogger implements \App\Contracts\Services\AuditLogger
{
    public array $records = [];

    public function record(string $action, ?string $entity = null, ?int $entityId = null, array $details = []): void
    {
        $this->records[] = compact('action', 'entity', 'entityId', 'details');
    }
}

return static function (): void {
    $repo = new InMemoryAbsenceRepository();
    $audit = new AbsenceSpyAuditLogger();
    $service = new AbsenceService($repo, $audit);

    $id = $service->createFromGuardian(7, [
        'aluno_id' => 10,
        'data_falta' => '2026-06-26',
        'motivo' => 'Doença',
        'observacao' => 'Febre',
    ], 'anexo.pdf');
    if ($id !== 55) throw new RuntimeException('Aviso não retornou id.');
    if (($repo->created[0]['turma_id'] ?? null) !== 2) throw new RuntimeException('Turma do aluno não foi preservada.');
    if (($audit->records[0]['action'] ?? null) !== 'enviar_aviso_falta') throw new RuntimeException('Envio de falta não auditou.');

    $service->updateStatus(55, 'abonado', 99);
    if (($repo->statuses[0]['status'] ?? null) !== 'abonado') throw new RuntimeException('Status não foi atualizado.');
    if (($audit->records[1]['action'] ?? null) !== 'alterar_aviso_falta') throw new RuntimeException('Alteração de falta não auditou.');

    $blocked = false;
    try {
        $service->createFromGuardian(7, ['aluno_id' => 999, 'data_falta' => '2026-06-26'], null);
    } catch (RuntimeException) {
        $blocked = true;
    }
    if (!$blocked) throw new RuntimeException('Aluno inválido não foi bloqueado.');
};
