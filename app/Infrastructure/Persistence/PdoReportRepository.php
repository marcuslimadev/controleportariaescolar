<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\ReportRepository;
use PDO;

final class PdoReportRepository implements ReportRepository
{
    public function __construct(private PDO $pdo) {}

    public function accessMovements(string $from, string $to): array
    {
        $query = $this->pdo->prepare(
            'SELECT r.*,a.nome aluno,t.nome turma,u.nome usuario
             FROM scp_registros_acesso r
             JOIN scp_alunos a ON a.id=r.aluno_id
             LEFT JOIN scp_turmas t ON t.id=a.turma_id
             JOIN scp_usuarios u ON u.id=r.usuario_id
             WHERE DATE(r.registrado_em) BETWEEN ? AND ?
             ORDER BY r.registrado_em DESC'
        );
        $query->execute([$from, $to]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
