<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\GuardianRepository;
use PDO;

final class PdoGuardianRepository implements GuardianRepository
{
    public function __construct(private PDO $pdo) {}

    public function findActiveIdByQrToken(string $token): ?int
    {
        $query = $this->pdo->prepare('SELECT id FROM scp_responsaveis WHERE qr_token=? AND ativo=1');
        $query->execute([$token]);
        $id = (int)$query->fetchColumn();

        return $id > 0 ? $id : null;
    }

    public function canWithdrawStudent(int $guardianId, int $studentId): bool
    {
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM scp_aluno_responsavel WHERE aluno_id=? AND responsavel_id=? AND autoriza_retirada=1');
        $query->execute([$studentId, $guardianId]);

        return (int)$query->fetchColumn() > 0;
    }
}
