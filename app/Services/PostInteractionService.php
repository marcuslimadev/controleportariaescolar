<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\PostInteractionRepository;
use App\Contracts\Services\AuditLogger;
use RuntimeException;

final class PostInteractionService
{
    public function __construct(
        private PostInteractionRepository $interactions,
        private AuditLogger $audit,
    ) {}

    public function toggleLike(int $postId, array $actor, string $visibilitySql, array $visibilityParams): void
    {
        if (!$this->interactions->canInteract($postId, $visibilitySql, $visibilityParams)) {
            throw new RuntimeException('Publicação não encontrada.');
        }

        $this->interactions->toggleLike($postId, $actor['responsavel_id'] ?? null, $actor['user_id'] ?? null);
        $this->audit->record('alternar_curtida_post', 'scp_posts', $postId);
    }

    public function confirmScience(int $postId, array $actor, string $visibilitySql, array $visibilityParams, ?string $ip, string $userAgent): void
    {
        if (!$this->interactions->canInteract($postId, $visibilitySql, $visibilityParams, true)) {
            throw new RuntimeException('Comunicado não encontrado.');
        }

        $this->interactions->confirmScience($postId, $actor['responsavel_id'] ?? null, $actor['user_id'] ?? null, $ip, $userAgent);
        $this->audit->record('confirmar_ciencia_post', 'scp_posts', $postId);
    }

    public function addComment(int $postId, string $comment, array $actor, string $visibilitySql, array $visibilityParams): void
    {
        $comment = trim($comment);
        if ($comment === '' || strlen($comment) > 1200) {
            throw new RuntimeException('Escreva um comentário com até 1200 caracteres.');
        }
        if (!$this->interactions->canInteract($postId, $visibilitySql, $visibilityParams)) {
            throw new RuntimeException('Publicação não encontrada.');
        }

        $id = $this->interactions->addComment($postId, $actor['responsavel_id'] ?? null, $actor['user_id'] ?? null, $comment);
        $this->audit->record('comentar_post', 'scp_post_comentarios', $id, ['post_id' => $postId]);
    }

    public function approvedCommentsForPosts(array $postIds): array
    {
        $grouped = [];
        foreach ($this->interactions->approvedCommentsForPosts($postIds) as $comment) {
            $grouped[(int)$comment['post_id']][] = $comment;
        }

        return $grouped;
    }

    public function pendingComments(): array
    {
        return $this->interactions->pendingComments();
    }

    public function moderateComment(int $commentId, string $status, int $moderatorId): void
    {
        if (!in_array($status, ['aprovado','rejeitado'], true)) {
            throw new RuntimeException('Status inválido.');
        }
        $this->interactions->moderateComment($commentId, $status, $moderatorId);
        $this->audit->record('moderar_comentario_post', 'scp_post_comentarios', $commentId, ['status' => $status]);
    }
}
