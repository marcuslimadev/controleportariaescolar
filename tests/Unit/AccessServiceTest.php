<?php
declare(strict_types=1);

use App\Contracts\Repositories\AccessLogRepository;
use App\Contracts\Repositories\GuardianRepository;
use App\Services\AccessService;

final class InMemoryGuardianRepository implements GuardianRepository
{
    public ?int $guardianId = 7;
    public array $authorized = [10 => true, 11 => true];

    public function findActiveIdByQrToken(string $token): ?int
    {
        return $token === 'TOKEN_OK' ? $this->guardianId : null;
    }

    public function canWithdrawStudent(int $guardianId, int $studentId): bool
    {
        return $guardianId === $this->guardianId && !empty($this->authorized[$studentId]);
    }
}

final class InMemoryAccessLogRepository implements AccessLogRepository
{
    public array $records = [];

    public function record(
        int $studentId,
        int $guardianId,
        string $type,
        int $operatorId,
        string $origin,
        ?string $note,
        bool $manual,
        ?string $ip
    ): void {
        $this->records[] = compact('studentId', 'guardianId', 'type', 'operatorId', 'origin', 'note', 'manual', 'ip');
    }
}

final class AccessSpyAuditLogger implements \App\Contracts\Services\AuditLogger
{
    public array $records = [];

    public function record(string $action, ?string $entity = null, ?int $entityId = null, array $details = []): void
    {
        $this->records[] = compact('action', 'entity', 'entityId', 'details');
    }
}

return static function (): void {
    $guardians = new InMemoryGuardianRepository();
    $logs = new InMemoryAccessLogRepository();
    $audit = new AccessSpyAuditLogger();
    $service = new AccessService($guardians, $logs, $audit);

    $result = $service->registerGuardianAccess('TOKEN_OK', [
        ['aluno_id' => 10, 'tipo' => 'entrada', 'manual' => false],
        ['aluno_id' => 11, 'tipo' => 'saida', 'manual' => true, 'observacao' => 'Correção autorizada'],
    ], 99, 'teste', '127.0.0.1');

    if (count($logs->records) !== 2) throw new RuntimeException('Acessos não registrados.');
    if (!str_contains($result['message'], '1 entrada e 1 saída')) throw new RuntimeException('Mensagem de registro incorreta.');
    if (($audit->records[1]['action'] ?? null) !== 'correcao_manual') throw new RuntimeException('Auditoria manual não registrada.');

    $blocked = false;
    try {
        $service->registerGuardianAccess('TOKEN_ERRADO', [['aluno_id' => 10, 'tipo' => 'entrada']], 99, 'teste', null);
    } catch (RuntimeException) {
        $blocked = true;
    }
    if (!$blocked) throw new RuntimeException('Token inválido não foi bloqueado.');
};
