<?php
declare(strict_types=1);

use App\Contracts\Repositories\BadgeRepository;
use App\Services\BadgeService;

final class InMemoryBadgeRepository implements BadgeRepository
{
    public array $guardians = [
        7 => ['id' => 7, 'nome' => 'Responsável', 'qr_token' => null, 'telefone' => '11999990000'],
    ];
    public array $updatedTokens = [];
    public array $issues = [];

    public function findGuardianByQrToken(string $token): ?array
    {
        return $token === 'TOKEN' ? ['id' => 7, 'nome' => 'Responsável', 'qr_token' => 'TOKEN'] : null;
    }

    public function findGuardianByApprovedInvite(string $inviteToken, int $guardianId): ?array
    {
        return $inviteToken === 'INVITE' && $guardianId === 7 ? $this->guardians[7] : null;
    }

    public function findActiveGuardianById(int $id): ?array { return $this->guardians[$id] ?? null; }

    public function findGuardianById(int $id): ?array { return $this->guardians[$id] ?? null; }

    public function updateGuardianQrToken(int $guardianId, string $token): void
    {
        $this->updatedTokens[$guardianId] = $token;
        $this->guardians[$guardianId]['qr_token'] = $token;
    }

    public function withdrawalChildren(int $guardianId): array
    {
        return [['nome' => 'Aluno', 'turma' => '1A']];
    }

    public function recordGuardianIssue(int $guardianId, int $issuedBy, string $token): void
    {
        $this->issues[] = compact('guardianId', 'issuedBy', 'token');
    }

    public function findActiveStudentSecurityBadge(int $studentId): ?array
    {
        return $studentId === 10 ? ['id' => 10, 'nome' => 'Aluno', 'qr_token' => 'STUDENT'] : null;
    }
}

final class BadgeSpyAuditLogger implements \App\Contracts\Services\AuditLogger
{
    public array $records = [];

    public function record(string $action, ?string $entity = null, ?int $entityId = null, array $details = []): void
    {
        $this->records[] = compact('action', 'entity', 'entityId', 'details');
    }
}

return static function (): void {
    $repo = new InMemoryBadgeRepository();
    $audit = new BadgeSpyAuditLogger();
    $service = new BadgeService($repo, $audit);

    $badge = $service->publicGuardianBadge(null, 'INVITE', 7, null);
    if (empty($badge['guardian']['qr_token'])) throw new RuntimeException('Token do responsável não foi garantido.');
    if (count($badge['children']) !== 1) throw new RuntimeException('Crianças autorizadas não foram carregadas.');

    $admin = $service->adminGuardianBadge(7, true, 99);
    if (($repo->issues[0]['issuedBy'] ?? null) !== 99) throw new RuntimeException('Emissão do crachá não foi registrada.');
    if (($audit->records[0]['action'] ?? null) !== 'emitir_cracha_responsavel') throw new RuntimeException('Emissão não auditou.');

    $security = $service->securityBadge(10);
    if (($security['qr_token'] ?? null) !== 'STUDENT') throw new RuntimeException('QR de segurança não carregou aluno.');
};
