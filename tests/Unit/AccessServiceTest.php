<?php
declare(strict_types=1);

use App\Contracts\Repositories\AccessLogRepository;
use App\Contracts\Repositories\GuardianRepository;
use App\Services\AccessService;

final class InMemoryGuardianRepository implements GuardianRepository
{
    public ?int $guardianId = 7;
    public array $authorized = [10 => true, 11 => true];
    public ?array $guardian = ['id' => 7, 'nome' => 'Responsável Teste', 'foto' => null, 'qr_token' => 'TOKEN_OK'];
    public array $children = [
        ['id' => 10, 'nome' => 'Aluno Entrada', 'foto' => null, 'turma' => '1A', 'ultimo' => null],
        ['id' => 11, 'nome' => 'Aluno Saída', 'foto' => null, 'turma' => '1A', 'ultimo' => 'entrada'],
    ];

    public function findActiveIdByQrToken(string $token): ?int
    {
        return $token === 'TOKEN_OK' ? $this->guardianId : null;
    }

    public function findActiveByQrToken(string $token): ?array
    {
        return $token === 'TOKEN_OK' ? $this->guardian : null;
    }

    public function authorizedChildrenForWithdrawal(int $guardianId): array
    {
        return $guardianId === $this->guardianId ? $this->children : [];
    }

    public function canWithdrawStudent(int $guardianId, int $studentId): bool
    {
        return $guardianId === $this->guardianId && !empty($this->authorized[$studentId]);
    }

    public function findIdByCpf(string $cpf): ?int
    {
        return $cpf === '11144477735' ? $this->guardianId : null;
    }

    public function findActiveByCpfOrPhone(string $digits): ?array
    {
        return $digits === '11144477735' ? ['id' => $this->guardianId, 'nome' => 'Responsável Teste', 'senha_hash' => \App\Support\PasswordService::hash('Senha@12345')] : null;
    }

    public function findActiveById(int $id): ?array
    {
        return $id === $this->guardianId ? $this->guardian : null;
    }

    public function updatePasswordHash(int $id, string $hash): void {}

    public function updatePhoto(int $id, string $photoUrl): void {}

    public function createFromInvite(array $invite): int
    {
        return $this->guardianId ?? 7;
    }

    public function updateFromInvite(int $guardianId, array $invite): void {}
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

final class InMemoryStudentRepository implements \App\Contracts\Repositories\StudentRepository
{
    public bool $securityBadgeExists = false;

    public function activeExistsByQrToken(string $token): bool
    {
        return $this->securityBadgeExists && $token === 'STUDENT_TOKEN';
    }

    public function createFromInvite(array $invite, string $qrToken): int
    {
        return 10;
    }

    public function linkGuardian(int $studentId, int $guardianId): void {}
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

    $lookup = new \App\Services\AccessLookupService($guardians, new InMemoryStudentRepository());
    $found = $lookup->lookupGuardianBadge('TOKEN_OK');
    if (empty($found['ok']) || count($found['children']) !== 2) throw new RuntimeException('Lookup do responsável falhou.');
    if (($found['children'][0]['sugerida'] ?? null) !== 'entrada') throw new RuntimeException('Sugestão de entrada incorreta.');
    if (($found['children'][1]['sugerida'] ?? null) !== 'saida') throw new RuntimeException('Sugestão de saída incorreta.');

    $students = new InMemoryStudentRepository();
    $students->securityBadgeExists = true;
    $lookup = new \App\Services\AccessLookupService($guardians, $students);
    $security = $lookup->lookupGuardianBadge('STUDENT_TOKEN');
    if (($security['message'] ?? '') === '') throw new RuntimeException('Crachá de segurança não foi identificado.');
};
