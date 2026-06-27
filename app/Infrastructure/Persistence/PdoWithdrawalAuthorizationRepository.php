<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\WithdrawalAuthorizationRepository;
use PDO;

final class PdoWithdrawalAuthorizationRepository implements WithdrawalAuthorizationRepository
{
    public function __construct(private PDO $pdo) {}

    public function childrenForGuardian(int $guardianId): array
    {
        $query = $this->pdo->prepare(
            'SELECT a.id,a.nome,t.nome turma
             FROM scp_aluno_responsavel ar
             JOIN scp_alunos a ON a.id=ar.aluno_id
             LEFT JOIN scp_turmas t ON t.id=a.turma_id
             WHERE ar.responsavel_id=? AND ar.autoriza_consulta=1 AND a.ativo=1 AND a.deleted_at IS NULL
             ORDER BY a.nome'
        );
        $query->execute([$guardianId]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardianCanAuthorize(int $guardianId, int $studentId): bool
    {
        $query = $this->pdo->prepare(
            'SELECT COUNT(*) FROM scp_aluno_responsavel ar JOIN scp_alunos a ON a.id=ar.aluno_id WHERE ar.responsavel_id=? AND ar.aluno_id=? AND ar.autoriza_consulta=1 AND a.ativo=1 AND a.deleted_at IS NULL'
        );
        $query->execute([$guardianId, $studentId]);

        return (bool)$query->fetchColumn();
    }

    public function create(array $data): int
    {
        $query = $this->pdo->prepare(
            'INSERT INTO scp_autorizacoes_retirada(aluno_id,responsavel_id,nome_autorizado,documento,telefone,valido_ate,observacao) VALUES(?,?,?,?,?,?,?)'
        );
        $query->execute([
            $data['aluno_id'],
            $data['responsavel_id'],
            $data['nome_autorizado'],
            $data['documento'],
            $data['telefone'],
            $data['valido_ate'],
            $data['observacao'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function listForGuardian(int $guardianId): array
    {
        $query = $this->pdo->prepare(
            'SELECT ar.*,a.nome aluno,t.nome turma
             FROM scp_autorizacoes_retirada ar
             JOIN scp_alunos a ON a.id=ar.aluno_id
             LEFT JOIN scp_turmas t ON t.id=a.turma_id
             WHERE ar.responsavel_id=?
             ORDER BY ar.created_at DESC
             LIMIT 80'
        );
        $query->execute([$guardianId]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeForGate(): array
    {
        return $this->pdo->query(
            "SELECT ar.*,a.nome aluno,t.nome turma,r.nome responsavel,r.telefone responsavel_telefone
             FROM scp_autorizacoes_retirada ar
             JOIN scp_alunos a ON a.id=ar.aluno_id
             JOIN scp_responsaveis r ON r.id=ar.responsavel_id
             LEFT JOIN scp_turmas t ON t.id=a.turma_id
             WHERE ar.status='ativa' AND ar.valido_ate>=CURDATE() AND a.deleted_at IS NULL AND r.deleted_at IS NULL
             ORDER BY ar.valido_ate ASC, ar.created_at DESC
             LIMIT 120"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status, ?int $operatorId = null): void
    {
        if ($status === 'usada') {
            $query = $this->pdo->prepare("UPDATE scp_autorizacoes_retirada SET status='usada', usado_por=?, usado_em=NOW() WHERE id=? AND status='ativa'");
            $query->execute([$operatorId, $id]);
            return;
        }

        $query = $this->pdo->prepare("UPDATE scp_autorizacoes_retirada SET status=? WHERE id=? AND status='ativa'");
        $query->execute([$status, $id]);
    }

    public function cancelForGuardian(int $id, int $guardianId): void
    {
        $query = $this->pdo->prepare("UPDATE scp_autorizacoes_retirada SET status='cancelada' WHERE id=? AND responsavel_id=? AND status='ativa'");
        $query->execute([$id, $guardianId]);
    }
}
