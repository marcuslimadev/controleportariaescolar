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

    public function pendingSummary(): array
    {
        $count = (int)$this->pdo->query("SELECT COUNT(*) FROM scp_convites_cadastro WHERE status='preenchido'")->fetchColumn();
        $latest = $this->pdo->query("SELECT MAX(preenchido_em) FROM scp_convites_cadastro WHERE status='preenchido'")->fetchColumn();

        return ['count' => $count, 'latest' => $latest ?: null];
    }

    public function approvalPreview(int $id): ?array
    {
        $query = $this->pdo->prepare(
            "SELECT c.*,u.nome criado_por_nome
             FROM scp_convites_cadastro c
             JOIN scp_usuarios u ON u.id=c.criado_por
             WHERE c.id=? AND c.status='preenchido'"
        );
        $query->execute([$id]);
        $invite = $query->fetch(PDO::FETCH_ASSOC);

        return $invite ?: null;
    }

    public function findByPublicToken(string $token): ?array
    {
        $query = $this->pdo->prepare('SELECT * FROM scp_convites_cadastro WHERE token_hash=? LIMIT 1');
        $query->execute([hash('sha256', $token)]);
        $invite = $query->fetch(PDO::FETCH_ASSOC);

        return $invite ?: null;
    }

    public function expire(int $id): void
    {
        $query = $this->pdo->prepare("UPDATE scp_convites_cadastro SET status='expirado' WHERE id=?");
        $query->execute([$id]);
    }

    public function fillByFamily(int $id, array $data): void
    {
        $query = $this->pdo->prepare(
            "UPDATE scp_convites_cadastro
             SET status='preenchido',responsavel_nome=?,responsavel_cpf=?,responsavel_email=?,responsavel_foto=?,aluno_nome=?,aluno_data_nascimento=?,aluno_foto=?,senha_hash=?,preenchido_em=NOW()
             WHERE id=? AND status='aguardando'"
        );
        $query->execute([
            $data['responsavel_nome'],
            $data['responsavel_cpf'],
            $data['responsavel_email'] ?: null,
            $data['responsavel_foto'],
            $data['aluno_nome'],
            $data['aluno_data_nascimento'] ?: null,
            $data['aluno_foto'],
            $data['senha_hash'],
            $id,
        ]);
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
