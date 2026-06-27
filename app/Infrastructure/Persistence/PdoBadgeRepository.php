<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\BadgeRepository;
use PDO;

final class PdoBadgeRepository implements BadgeRepository
{
    public function __construct(private PDO $pdo) {}

    public function findGuardianByQrToken(string $token): ?array
    {
        $query = $this->pdo->prepare('SELECT * FROM scp_responsaveis WHERE qr_token=? AND ativo=1');
        $query->execute([$token]);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findGuardianByApprovedInvite(string $inviteToken, int $guardianId): ?array
    {
        $query = $this->pdo->prepare(
            "SELECT r.*
             FROM scp_convites_cadastro c
             JOIN scp_responsaveis r ON r.id=c.responsavel_id
             WHERE c.token_hash=? AND c.responsavel_id=? AND c.status='aprovado'"
        );
        $query->execute([hash('sha256', $inviteToken), $guardianId]);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findActiveGuardianById(int $id): ?array
    {
        $query = $this->pdo->prepare('SELECT * FROM scp_responsaveis WHERE id=? AND ativo=1');
        $query->execute([$id]);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findGuardianById(int $id): ?array
    {
        $query = $this->pdo->prepare('SELECT * FROM scp_responsaveis WHERE id=?');
        $query->execute([$id]);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function updateGuardianQrToken(int $guardianId, string $token): void
    {
        $query = $this->pdo->prepare('UPDATE scp_responsaveis SET qr_token=? WHERE id=?');
        $query->execute([$token, $guardianId]);
    }

    public function withdrawalChildren(int $guardianId): array
    {
        $query = $this->pdo->prepare(
            'SELECT a.nome,a.foto,t.nome turma
             FROM scp_alunos a
             JOIN scp_aluno_responsavel ar ON ar.aluno_id=a.id
             LEFT JOIN scp_turmas t ON t.id=a.turma_id
             WHERE ar.responsavel_id=? AND ar.autoriza_retirada=1 AND a.ativo=1
             ORDER BY a.nome'
        );
        $query->execute([$guardianId]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recordGuardianIssue(int $guardianId, int $issuedBy, string $token): void
    {
        $query = $this->pdo->prepare('INSERT INTO scp_crachas_responsavel_emitidos(responsavel_id,emitido_por,token_no_momento) VALUES(?,?,?)');
        $query->execute([$guardianId, $issuedBy, $token]);
    }

    public function findActiveStudentSecurityBadge(int $studentId): ?array
    {
        $query = $this->pdo->prepare('SELECT id,nome,qr_token FROM scp_alunos WHERE id=? AND ativo=1');
        $query->execute([$studentId]);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
