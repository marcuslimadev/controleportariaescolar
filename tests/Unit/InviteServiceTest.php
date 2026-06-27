<?php
declare(strict_types=1);

use App\Contracts\Repositories\InviteRepository;
use App\Services\InviteService;

final class InMemoryInviteRepository implements InviteRepository
{
    public array $created = [];
    public array $filled = [];
    public ?array $publicInvite = ['id' => 33, 'status' => 'aguardando', 'expira_em' => '2999-01-01 00:00:00'];

    public function create(string $phone, string $tokenHash, int $createdBy): int
    {
        $this->created[] = compact('phone', 'tokenHash', 'createdBy');
        return 123;
    }

    public function expireOld(): void {}

    public function pendingList(int $limit = 30): array { return []; }

    public function pendingSummary(): array { return ['count' => 1, 'latest' => '2026-06-26 08:00:00']; }

    public function approvalPreview(int $id): ?array { return $id === 33 ? ['id' => 33, 'status' => 'preenchido'] : null; }

    public function findByPublicToken(string $token): ?array
    {
        return $token === 'PUBLIC_TOKEN' ? $this->publicInvite : null;
    }

    public function expire(int $id): void
    {
        $this->publicInvite['status'] = 'expirado';
    }

    public function fillByFamily(int $id, array $data): void
    {
        $this->filled[] = compact('id', 'data');
    }

    public function findReadyForApproval(int $id): ?array { return null; }

    public function markApproved(int $id, int $approvedBy, int $guardianId, int $studentId): void {}
}

return static function (): void {
    $repo = new InMemoryInviteRepository();
    $service = new InviteService(
        $repo,
        new InMemoryGuardianRepository(),
        new InMemoryStudentRepository(),
        new AccessSpyAuditLogger(),
        new class {
            public function beginTransaction(): void {}
            public function commit(): void {}
            public function rollBack(): void {}
            public function inTransaction(): bool { return false; }
        }
    );

    $invite = $service->createInvite('(91) 96321-42134', 77);
    if (($invite['id'] ?? null) !== 123) throw new RuntimeException('Convite não retornou id.');
    if (($repo->created[0]['phone'] ?? null) !== '919632142134') throw new RuntimeException('Telefone não foi normalizado.');
    if (($repo->created[0]['createdBy'] ?? null) !== 77) throw new RuntimeException('Criador não foi registrado.');
    if (($service->pendingSummary()['count'] ?? null) !== 1) throw new RuntimeException('Resumo de pendências falhou.');
    if (($service->approvalPreview(33)['status'] ?? null) !== 'preenchido') throw new RuntimeException('Prévia de aprovação falhou.');

    $blocked = false;
    try {
        $service->createInvite('123', 77);
    } catch (RuntimeException) {
        $blocked = true;
    }
    if (!$blocked) throw new RuntimeException('Telefone inválido não foi bloqueado.');

    $audit = new AccessSpyAuditLogger();
    $onboarding = new \App\Services\FamilyOnboardingService($repo, $audit);
    $state = $onboarding->getInvite('PUBLIC_TOKEN');
    if (($state['invalid'] ?? true) !== false) throw new RuntimeException('Convite público válido foi marcado como inválido.');
    $onboarding->fillInvite(33, [
        'responsavel_nome' => 'Responsável Completo',
        'cpf' => '111.444.777-35',
        'email' => 'teste@example.com',
        'aluno_nome' => 'Aluno Completo',
        'data_nascimento' => '2020-01-02',
        'senha' => 'Senha@12345',
        'confirmar_senha' => 'Senha@12345',
    ], 'foto-responsavel.jpg', 'foto-aluno.jpg');
    if (($repo->filled[0]['data']['responsavel_cpf'] ?? null) !== '11144477735') throw new RuntimeException('CPF não foi normalizado.');
    if (($audit->records[0]['action'] ?? null) !== 'preencher_convite_cadastro') throw new RuntimeException('Preenchimento não auditou.');

    $blocked = false;
    try {
        $onboarding->fillInvite(33, [
            'responsavel_nome' => 'A',
            'cpf' => '123',
            'aluno_nome' => 'B',
            'senha' => '123',
            'confirmar_senha' => '456',
        ], 'foto-responsavel.jpg', 'foto-aluno.jpg');
    } catch (RuntimeException) {
        $blocked = true;
    }
    if (!$blocked) throw new RuntimeException('Cadastro familiar inválido não foi bloqueado.');
};
