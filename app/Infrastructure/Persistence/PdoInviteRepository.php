<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\InviteRepository;
use PDO;

final class PdoInviteRepository implements InviteRepository
{
    public function __construct(private PDO $pdo) {}

    public function create(string $phone, string $tokenHash, int $createdBy): int
    {
        $query = $this->pdo->prepare(
            "INSERT INTO scp_convites_cadastro(telefone,token_hash,criado_por,expira_em) VALUES(?,?,?,DATE_ADD(NOW(),INTERVAL 24 HOUR))"
        );
        $query->execute([$phone, $tokenHash, $createdBy]);

        return (int)$this->pdo->lastInsertId();
    }

    public function expireOld(): void
    {
        $this->pdo->exec("UPDATE scp_convites_cadastro SET status='expirado' WHERE status='aguardando' AND expira_em<NOW()");
    }

    public function pendingList(int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        $query = $this->pdo->query(
            "SELECT c.*,u.nome criado_por_nome
             FROM scp_convites_cadastro c
             JOIN scp_usuarios u ON u.id=c.criado_por
             WHERE c.status IN ('aguardando','preenchido')
             ORDER BY FIELD(c.status,'preenchido','aguardando'),c.created_at DESC
             LIMIT {$limit}"
        );

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findReadyForApproval(int $id): ?array
    {
        $query = $this->pdo->prepare("SELECT * FROM scp_convites_cadastro WHERE id=? AND status='preenchido' AND expira_em>NOW() FOR UPDATE");
        $query->execute([$id]);
        $invite = $query->fetch(PDO::FETCH_ASSOC);

        return $invite ?: null;
    }

    public function markApproved(int $id, int $approvedBy, int $guardianId, int $studentId): void
    {
        $query = $this->pdo->prepare(
            "UPDATE scp_convites_cadastro SET status='aprovado',aprovado_em=NOW(),aprovado_por=?,responsavel_id=?,aluno_id=? WHERE id=?"
        );
        $query->execute([$approvedBy, $guardianId, $studentId, $id]);
    }
}
