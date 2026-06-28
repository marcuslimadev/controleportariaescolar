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
             WHERE DATE(r.registrado_em) BETWEEN ? AND ? AND a.deleted_at IS NULL AND u.deleted_at IS NULL
             ORDER BY r.registrado_em DESC'
        );
        $query->execute([$from, $to]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function dashboardSummary(string $date): array
    {
        $access = $this->pdo->prepare(
            "SELECT
                COUNT(*) total,
                SUM(tipo='entrada') entradas,
                SUM(tipo='saida') saidas,
                COUNT(DISTINCT aluno_id) alunos
             FROM scp_registros_acesso
             WHERE DATE(registrado_em)=?"
        );
        $access->execute([$date]);
        $accessRow = $access->fetch(PDO::FETCH_ASSOC) ?: [];

        $posts = $this->pdo->query(
            "SELECT
                COUNT(*) total,
                SUM(publico='publico') publicos,
                SUM(publico<>'publico') privados
             FROM scp_posts
             WHERE status='publicado' AND deleted_at IS NULL AND publicado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        )->fetch(PDO::FETCH_ASSOC) ?: [];

        $comments = $this->pdo->query("SELECT COUNT(*) FROM scp_post_comentarios WHERE status='pendente'")->fetchColumn();
        $invites = $this->pdo->query("SELECT COUNT(*) FROM scp_convites_cadastro WHERE status='preenchido'")->fetchColumn();
        $absences = $this->pdo->query("SELECT COUNT(*) FROM scp_avisos_falta WHERE status='enviado'")->fetchColumn();

        return [
            'data' => $date,
            'acessos_total' => (int)($accessRow['total'] ?? 0),
            'entradas' => (int)($accessRow['entradas'] ?? 0),
            'saidas' => (int)($accessRow['saidas'] ?? 0),
            'alunos_movimentados' => (int)($accessRow['alunos'] ?? 0),
            'posts_7d' => (int)($posts['total'] ?? 0),
            'posts_publicos_7d' => (int)($posts['publicos'] ?? 0),
            'posts_privados_7d' => (int)($posts['privados'] ?? 0),
            'comentarios_pendentes' => (int)$comments,
            'cadastros_pendentes' => (int)$invites,
            'avisos_falta_pendentes' => (int)$absences,
        ];
    }
}
