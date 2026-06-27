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
}
