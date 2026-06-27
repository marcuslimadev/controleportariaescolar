<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\AbsenceRepository;
use PDO;

final class PdoAbsenceRepository implements AbsenceRepository
{
    public function __construct(private PDO $pdo) {}

    public function childrenForGuardian(int $guardianId): array
    {
        $query = $this->pdo->prepare(
            'SELECT a.id,a.nome,a.turma_id,t.nome turma
             FROM scp_aluno_responsavel ar
             JOIN scp_alunos a ON a.id=ar.aluno_id
             LEFT JOIN scp_turmas t ON t.id=a.turma_id
             WHERE ar.responsavel_id=? AND ar.autoriza_consulta=1 AND a.ativo=1
             ORDER BY a.nome'
        );
        $query->execute([$guardianId]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $query = $this->pdo->prepare(
            'INSERT INTO scp_avisos_falta(aluno_id,responsavel_id,turma_id,data_falta,motivo,observacao,anexo_url) VALUES(?,?,?,?,?,?,?)'
        );
        $query->execute([
            $data['aluno_id'],
            $data['responsavel_id'],
            $data['turma_id'],
            $data['data_falta'],
            $data['motivo'],
            $data['observacao'],
            $data['anexo_url'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status, int $reviewedBy): void
    {
        $query = $this->pdo->prepare(
            'UPDATE scp_avisos_falta SET status=?, visualizado_em=COALESCE(visualizado_em,NOW()), analisado_por=?, analisado_em=NOW() WHERE id=?'
        );
        $query->execute([$status, $reviewedBy, $id]);
    }

    public function listForAdmin(?string $status = null): array
    {
        $params = [];
        $where = '';
        if ($status !== null) {
            $where = 'WHERE af.status=?';
            $params[] = $status;
        }
        $query = $this->pdo->prepare(
            "SELECT af.*, a.nome aluno, t.nome turma, r.nome responsavel
             FROM scp_avisos_falta af
             JOIN scp_alunos a ON a.id=af.aluno_id
             LEFT JOIN scp_turmas t ON t.id=af.turma_id
             JOIN scp_responsaveis r ON r.id=af.responsavel_id
             {$where}
             ORDER BY af.data_falta DESC, af.id DESC"
        );
        $query->execute($params);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
