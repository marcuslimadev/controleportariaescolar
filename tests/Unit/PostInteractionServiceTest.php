<?php
declare(strict_types=1);

use App\Contracts\Repositories\PostInteractionRepository;
use App\Services\PostInteractionService;

final class InMemoryPostInteractionRepository implements PostInteractionRepository
{
    public bool $allowed = true;
    public array $likes = [];
    public array $sciences = [];

    public function canInteract(int $postId, string $visibilitySql, array $visibilityParams, bool $requiresScience = false): bool
    {
        return $this->allowed && $postId === 10;
    }

    public function toggleLike(int $postId, ?int $guardianId, ?int $userId): void
    {
        $this->likes[] = compact('postId', 'guardianId', 'userId');
    }

    public function confirmScience(int $postId, ?int $guardianId, ?int $userId, ?string $ip, string $userAgent): void
    {
        $this->sciences[] = compact('postId', 'guardianId', 'userId', 'ip', 'userAgent');
    }
}

return static function (): void {
    $repo = new InMemoryPostInteractionRepository();
    $audit = new AccessSpyAuditLogger();
    $service = new PostInteractionService($repo, $audit);

    $service->toggleLike(10, ['responsavel_id' => 5], '1=1', []);
    if (($repo->likes[0]['guardianId'] ?? null) !== 5) throw new RuntimeException('Curtida do responsável não foi registrada.');
    if (($audit->records[0]['action'] ?? null) !== 'alternar_curtida_post') throw new RuntimeException('Curtida não auditou.');

    $service->confirmScience(10, ['user_id' => 8], '1=1', [], '127.0.0.1', 'Teste');
    if (($repo->sciences[0]['userId'] ?? null) !== 8) throw new RuntimeException('Ciência do usuário não foi registrada.');
    if (($audit->records[1]['action'] ?? null) !== 'confirmar_ciencia_post') throw new RuntimeException('Ciência não auditou.');

    $repo->allowed = false;
    $blocked = false;
    try {
        $service->toggleLike(10, ['user_id' => 8], '0=1', []);
    } catch (RuntimeException) {
        $blocked = true;
    }
    if (!$blocked) throw new RuntimeException('Interação sem visibilidade não foi bloqueada.');
};
