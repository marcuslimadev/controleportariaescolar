<?php
declare(strict_types=1);

use App\Contracts\Repositories\InviteRepository;
use App\Services\InviteService;

final class InMemoryInviteRepository implements InviteRepository
{
    public array $created = [];

    public function create(string $phone, string $tokenHash, int $createdBy): int
    {
        $this->created[] = compact('phone', 'tokenHash', 'createdBy');
        return 123;
    }

    public function expireOld(): void {}

    public function pendingList(int $limit = 30): array { return []; }

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

    $blocked = false;
    try {
        $service->createInvite('123', 77);
    } catch (RuntimeException) {
        $blocked = true;
    }
    if (!$blocked) throw new RuntimeException('Telefone inválido não foi bloqueado.');
};
