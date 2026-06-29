<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\GuardianPortalRepository;
use PDO;

final class PdoGuardianPortalRepository implements GuardianPortalRepository
{
    public function __construct(private PDO $pdo) {}

    public function children(int $guardianId): array
    {
        $query = $this->pdo->prepare(
            "SELECT a.id,a.nome,a.foto,a.qr_token,t.nome turma,
                (SELECT r.tipo FROM scp_registros_acesso r WHERE r.aluno_id=a.id ORDER BY r.registrado_em DESC,r.id DESC LIMIT 1) ultimo_tipo,
                (SELECT r.registrado_em FROM scp_registros_acesso r WHERE r.aluno_id=a.id ORDER BY r.registrado_em DESC,r.id DESC LIMIT 1) ultimo_registro
             FROM scp_aluno_responsavel ar
             JOIN scp_alunos a ON a.id=ar.aluno_id
             LEFT JOIN scp_turmas t ON t.id=a.turma_id
             WHERE ar.responsavel_id=? AND ar.autoriza_consulta=1 AND a.ativo=1 AND a.deleted_at IS NULL
             ORDER BY a.nome"
        );
        $query->execute([$guardianId]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function movements(int $guardianId, string $from, string $to): array
    {
        $query = $this->pdo->prepare(
            'SELECT a.nome aluno,t.nome turma,r.tipo,r.registrado_em,r.origem,resp.nome responsavel
             FROM scp_aluno_responsavel ar
             JOIN scp_alunos a ON a.id=ar.aluno_id
             LEFT JOIN scp_turmas t ON t.id=a.turma_id
             LEFT JOIN scp_registros_acesso r ON r.aluno_id=a.id AND DATE(r.registrado_em) BETWEEN ? AND ?
             LEFT JOIN scp_responsaveis resp ON resp.id=r.responsavel_id
             WHERE ar.responsavel_id=? AND ar.autoriza_consulta=1 AND a.deleted_at IS NULL
             ORDER BY r.registrado_em DESC'
        );
        $query->execute([$from, $to, $guardianId]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function absences(int $guardianId): array
    {
        $query = $this->pdo->prepare(
            'SELECT af.*, a.nome aluno, t.nome turma
             FROM scp_avisos_falta af
             JOIN scp_alunos a ON a.id=af.aluno_id
             LEFT JOIN scp_turmas t ON t.id=af.turma_id
             WHERE af.responsavel_id=? AND a.deleted_at IS NULL
             ORDER BY af.data_falta DESC, af.id DESC'
        );
        $query->execute([$guardianId]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
