<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\FrequencyRepository;
use PDO;

final class PdoFrequencyRepository implements FrequencyRepository
{
    public function __construct(private PDO $pdo) {}

    public function classesForActor(string $role, int $professorId): array
    {
        $params = [];
        $where = 't.ativo=1';
        if ($role === 'professor') {
            $where .= ' AND t.id IN (SELECT turma_id FROM scp_professor_turma WHERE professor_id=?)';
            $params[] = $professorId;
        }

        $query = $this->pdo->prepare("SELECT t.id,t.nome FROM scp_turmas t WHERE {$where} ORDER BY t.nome");
        $query->execute($params);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function dailyRows(string $date, ?int $classId, string $studentName, string $role, int $professorId): array
    {
        $params = [$date, $date, $date];
        $where = 'a.ativo=1';

        if ($classId !== null && $classId > 0) {
            $where .= ' AND a.turma_id=?';
            $params[] = $classId;
        }
        if ($studentName !== '') {
            $where .= ' AND a.nome LIKE ?';
            $params[] = '%' . $studentName . '%';
        }
        if ($role === 'professor') {
            $where .= ' AND a.turma_id IN (SELECT turma_id FROM scp_professor_turma WHERE professor_id=?)';
            $params[] = $professorId;
        }

        $query = $this->pdo->prepare(
            "SELECT a.id,a.nome,t.nome turma,
                (SELECT MAX(registrado_em) FROM scp_registros_acesso r WHERE r.aluno_id=a.id AND r.tipo='entrada' AND DATE(r.registrado_em)=?) ultima_entrada,
                (SELECT MAX(registrado_em) FROM scp_registros_acesso r WHERE r.aluno_id=a.id AND r.tipo='saida' AND DATE(r.registrado_em)=?) ultima_saida,
                (SELECT CONCAT(status,'|',motivo) FROM scp_avisos_falta af WHERE af.aluno_id=a.id AND af.data_falta=? ORDER BY af.id DESC LIMIT 1) aviso
             FROM scp_alunos a
             LEFT JOIN scp_turmas t ON t.id=a.turma_id
             WHERE {$where}
             ORDER BY a.nome"
        );
        $query->execute($params);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
