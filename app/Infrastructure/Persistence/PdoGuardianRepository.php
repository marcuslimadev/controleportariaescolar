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

    public function findActiveByQrToken(string $token): ?array
    {
        $query = $this->pdo->prepare('SELECT id,nome,foto,qr_token FROM scp_responsaveis WHERE qr_token=? AND ativo=1');
        $query->execute([$token]);
        $guardian = $query->fetch(PDO::FETCH_ASSOC);

        return $guardian ?: null;
    }

    public function authorizedChildrenForWithdrawal(int $guardianId): array
    {
        $query = $this->pdo->prepare(
            "SELECT a.id,a.nome,a.foto,t.nome turma,
                (SELECT tipo FROM scp_registros_acesso r WHERE r.aluno_id=a.id ORDER BY r.registrado_em DESC,r.id DESC LIMIT 1) ultimo
             FROM scp_alunos a
             JOIN scp_aluno_responsavel ar ON ar.aluno_id=a.id
             LEFT JOIN scp_turmas t ON t.id=a.turma_id
             WHERE ar.responsavel_id=? AND ar.autoriza_retirada=1 AND a.ativo=1
             ORDER BY a.nome"
        );
        $query->execute([$guardianId]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function canWithdrawStudent(int $guardianId, int $studentId): bool
    {
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM scp_aluno_responsavel WHERE aluno_id=? AND responsavel_id=? AND autoriza_retirada=1');
        $query->execute([$studentId, $guardianId]);

        return (int)$query->fetchColumn() > 0;
    }
}
