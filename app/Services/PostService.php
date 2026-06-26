<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\PostRepository;
use App\Contracts\Services\AuditLogger;
use RuntimeException;

final class PostService
{
    public function __construct(
        private PostRepository $posts,
        private AuditLogger $audit,
    ) {}

    public function deletePost(int $id, int $actorId, string $actorRole): void
    {
        $post = $this->posts->findActiveById($id);
        if (!$post) {
            throw new RuntimeException('Publicação não encontrada.');
        }

        if ($actorRole === 'secretaria' && (int)$post['autor_id'] !== $actorId && ($post['autor_perfil'] ?? '') !== 'secretaria') {
            throw new RuntimeException('Você só pode excluir publicações da secretaria ou criadas por você.');
        }

        $this->posts->softDelete($id);
        $this->audit->record('excluir_post', 'scp_posts', $id, [
            'titulo' => $post['titulo'] ?? null,
            'status' => $post['status'] ?? null,
        ]);
    }
}
