<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\PostInteractionRepository;
use PDO;

final class PdoPostInteractionRepository implements PostInteractionRepository
{
    public function __construct(private PDO $pdo) {}

    public function canInteract(int $postId, string $visibilitySql, array $visibilityParams, bool $requiresScience = false): bool
    {
        $extra = $requiresScience ? ' AND p.exige_ciencia=1' : '';
        $query = $this->pdo->prepare("SELECT p.id FROM scp_posts p WHERE p.id=? AND p.status='publicado' AND p.deleted_at IS NULL{$extra} AND {$visibilitySql}");
        $query->execute(array_merge([$postId], $visibilityParams));

        return (bool)$query->fetchColumn();
    }

    public function toggleLike(int $postId, ?int $guardianId, ?int $userId): void
    {
        if ($guardianId) {
            $check = $this->pdo->prepare('SELECT id FROM scp_post_curtidas WHERE post_id=? AND responsavel_id=?');
            $check->execute([$postId, $guardianId]);
            $id = (int)$check->fetchColumn();
            if ($id) {
                $this->pdo->prepare('DELETE FROM scp_post_curtidas WHERE id=?')->execute([$id]);
                return;
            }
            $this->pdo->prepare('INSERT INTO scp_post_curtidas(post_id,responsavel_id) VALUES(?,?)')->execute([$postId, $guardianId]);
            return;
        }

        $check = $this->pdo->prepare('SELECT id FROM scp_post_curtidas WHERE post_id=? AND usuario_id=?');
        $check->execute([$postId, $userId]);
        $id = (int)$check->fetchColumn();
        if ($id) {
            $this->pdo->prepare('DELETE FROM scp_post_curtidas WHERE id=?')->execute([$id]);
            return;
        }
        $this->pdo->prepare('INSERT INTO scp_post_curtidas(post_id,usuario_id) VALUES(?,?)')->execute([$postId, $userId]);
    }

    public function confirmScience(int $postId, ?int $guardianId, ?int $userId, ?string $ip, string $userAgent): void
    {
        if ($guardianId) {
            $query = $this->pdo->prepare('INSERT IGNORE INTO scp_post_ciencias(post_id,responsavel_id,ip,user_agent) VALUES(?,?,?,?)');
            $query->execute([$postId, $guardianId, $ip, substr($userAgent, 0, 500)]);
            return;
        }

        $query = $this->pdo->prepare('INSERT IGNORE INTO scp_post_ciencias(post_id,usuario_id,ip,user_agent) VALUES(?,?,?,?)');
        $query->execute([$postId, $userId, $ip, substr($userAgent, 0, 500)]);
    }

    public function addComment(int $postId, ?int $guardianId, ?int $userId, string $comment): int
    {
        $query = $this->pdo->prepare('INSERT INTO scp_post_comentarios(post_id,responsavel_id,usuario_id,comentario) VALUES(?,?,?,?)');
        $query->execute([$postId, $guardianId, $userId, $comment]);

        return (int)$this->pdo->lastInsertId();
    }

    public function approvedCommentsForPosts(array $postIds): array
    {
        $postIds = array_values(array_filter(array_map('intval', $postIds), fn(int $id): bool => $id > 0));
        if (!$postIds) return [];
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $query = $this->pdo->prepare(
            "SELECT c.post_id,c.comentario,c.created_at,COALESCE(r.nome,u.nome) autor,
                    CASE WHEN c.responsavel_id IS NOT NULL THEN 'responsavel' ELSE u.perfil END perfil
             FROM scp_post_comentarios c
             LEFT JOIN scp_responsaveis r ON r.id=c.responsavel_id
             LEFT JOIN scp_usuarios u ON u.id=c.usuario_id
             WHERE c.status='aprovado' AND c.post_id IN ({$placeholders})
             ORDER BY c.created_at ASC"
        );
        $query->execute($postIds);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function pendingComments(): array
    {
        return $this->pdo->query(
            "SELECT c.*, p.titulo post_titulo, COALESCE(r.nome,u.nome) autor,
                    CASE WHEN c.responsavel_id IS NOT NULL THEN 'responsavel' ELSE u.perfil END perfil
             FROM scp_post_comentarios c
             JOIN scp_posts p ON p.id=c.post_id
             LEFT JOIN scp_responsaveis r ON r.id=c.responsavel_id
             LEFT JOIN scp_usuarios u ON u.id=c.usuario_id
             WHERE c.status='pendente' AND p.deleted_at IS NULL
             ORDER BY c.created_at ASC
             LIMIT 100"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function moderateComment(int $commentId, string $status, int $moderatorId): void
    {
        $query = $this->pdo->prepare("UPDATE scp_post_comentarios SET status=?, moderado_por=?, moderado_em=NOW() WHERE id=? AND status='pendente'");
        $query->execute([$status, $moderatorId, $commentId]);
    }
}
