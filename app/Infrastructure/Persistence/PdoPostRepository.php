<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\PostRepository;
use PDO;

final class PdoPostRepository implements PostRepository
{
    public function __construct(private PDO $pdo) {}

    public function findActiveById(int $id): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT p.*, u.perfil autor_perfil FROM scp_posts p JOIN scp_usuarios u ON u.id=p.autor_id WHERE p.id=? AND p.deleted_at IS NULL'
        );
        $query->execute([$id]);
        $post = $query->fetch(PDO::FETCH_ASSOC);

        return $post ?: null;
    }

    public function softDelete(int $id): void
    {
        $query = $this->pdo->prepare("UPDATE scp_posts SET deleted_at=NOW(), status='arquivado', fixado=0 WHERE id=?");
        $query->execute([$id]);
    }
}
