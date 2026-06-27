<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\NotificationRepository;
use PDO;

final class PdoNotificationRepository implements NotificationRepository
{
    public function __construct(private PDO $pdo) {}

    public function notifyPostPublished(int $postId, array $postData, int $actorId): void
    {
        $scope = (string)($postData['publico'] ?? '');
        if ($scope === 'publico') return;

        $title = 'Nova publicação';
        $message = substr((string)($postData['titulo'] ?? 'Comunicado da escola'), 0, 255);
        $link = 'feed.php';
        $users = $this->targetUsers($scope, (int)($postData['turma_id'] ?? 0), $actorId);
        $guardians = $this->targetGuardians($scope, (int)($postData['turma_id'] ?? 0), (int)($postData['aluno_id'] ?? 0));

        foreach ($users as $userId) {
            $this->insertOnce((int)$userId, null, $title, $message, $link, $postId);
        }
        foreach ($guardians as $guardianId) {
            $this->insertOnce(null, (int)$guardianId, $title, $message, $link, $postId);
        }
    }

    public function unreadCount(?int $userId, ?int $guardianId): int
    {
        $where = $guardianId ? 'responsavel_id=?' : 'usuario_id=?';
        $query = $this->pdo->prepare("SELECT COUNT(*) FROM scp_notificacoes WHERE {$where} AND lida_em IS NULL");
        $query->execute([$guardianId ?: $userId]);

        return (int)$query->fetchColumn();
    }

    public function listForActor(?int $userId, ?int $guardianId): array
    {
        $where = $guardianId ? 'responsavel_id=?' : 'usuario_id=?';
        $query = $this->pdo->prepare("SELECT * FROM scp_notificacoes WHERE {$where} ORDER BY lida_em IS NULL DESC, created_at DESC LIMIT 100");
        $query->execute([$guardianId ?: $userId]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAllRead(?int $userId, ?int $guardianId): void
    {
        $where = $guardianId ? 'responsavel_id=?' : 'usuario_id=?';
        $query = $this->pdo->prepare("UPDATE scp_notificacoes SET lida_em=COALESCE(lida_em,NOW()) WHERE {$where}");
        $query->execute([$guardianId ?: $userId]);
    }

    private function targetUsers(string $scope, int $classId, int $actorId): array
    {
        if ($scope === 'toda_escola') {
            $query = $this->pdo->prepare("SELECT id FROM scp_usuarios WHERE ativo=1 AND deleted_at IS NULL AND id<>?");
            $query->execute([$actorId]);
            return $query->fetchAll(PDO::FETCH_COLUMN);
        }
        if ($scope === 'equipe') {
            $query = $this->pdo->prepare("SELECT id FROM scp_usuarios WHERE ativo=1 AND deleted_at IS NULL AND id<>?");
            $query->execute([$actorId]);
            return $query->fetchAll(PDO::FETCH_COLUMN);
        }
        if ($scope === 'turma' && $classId > 0) {
            $query = $this->pdo->prepare(
                "SELECT DISTINCT u.id
                 FROM scp_usuarios u
                 WHERE u.ativo=1 AND u.deleted_at IS NULL AND u.id<>?
                   AND (
                     u.perfil IN ('admin','secretaria')
                     OR u.id IN (
                       SELECT pr.usuario_id
                       FROM scp_professores pr
                       JOIN scp_professor_turma pt ON pt.professor_id=pr.id
                       WHERE pt.turma_id=? AND pr.ativo=1 AND pr.usuario_id IS NOT NULL
                     )
                   )"
            );
            $query->execute([$actorId, $classId]);
            return $query->fetchAll(PDO::FETCH_COLUMN);
        }
        if ($scope === 'aluno') {
            $query = $this->pdo->prepare("SELECT id FROM scp_usuarios WHERE ativo=1 AND deleted_at IS NULL AND perfil IN ('admin','secretaria') AND id<>?");
            $query->execute([$actorId]);
            return $query->fetchAll(PDO::FETCH_COLUMN);
        }

        return [];
    }

    private function insertOnce(?int $userId, ?int $guardianId, string $title, string $message, string $link, int $postId): void
    {
        $query = $this->pdo->prepare(
            "INSERT INTO scp_notificacoes(usuario_id,responsavel_id,titulo,mensagem,link,origem_tipo,origem_id)
             SELECT ?,?,?,?,?, 'post', ?
             WHERE NOT EXISTS (
               SELECT 1 FROM scp_notificacoes
               WHERE usuario_id <=> ? AND responsavel_id <=> ? AND origem_tipo='post' AND origem_id=?
             )"
        );
        $query->execute([$userId, $guardianId, $title, $message, $link, $postId, $userId, $guardianId, $postId]);
    }

    private function targetGuardians(string $scope, int $classId, int $studentId): array
    {
        if ($scope === 'toda_escola') {
            return $this->pdo->query('SELECT id FROM scp_responsaveis WHERE ativo=1 AND deleted_at IS NULL')->fetchAll(PDO::FETCH_COLUMN);
        }
        if ($scope === 'turma' && $classId > 0) {
            $query = $this->pdo->prepare(
                "SELECT DISTINCT r.id
                 FROM scp_responsaveis r
                 JOIN scp_aluno_responsavel ar ON ar.responsavel_id=r.id
                 JOIN scp_alunos a ON a.id=ar.aluno_id
                 WHERE r.ativo=1 AND r.deleted_at IS NULL AND a.deleted_at IS NULL AND a.turma_id=?"
            );
            $query->execute([$classId]);
            return $query->fetchAll(PDO::FETCH_COLUMN);
        }
        if ($scope === 'aluno' && $studentId > 0) {
            $query = $this->pdo->prepare(
                "SELECT DISTINCT r.id
                 FROM scp_responsaveis r
                 JOIN scp_aluno_responsavel ar ON ar.responsavel_id=r.id
                 WHERE r.ativo=1 AND r.deleted_at IS NULL AND ar.aluno_id=?"
            );
            $query->execute([$studentId]);
            return $query->fetchAll(PDO::FETCH_COLUMN);
        }

        return [];
    }
}
