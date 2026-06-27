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
}
