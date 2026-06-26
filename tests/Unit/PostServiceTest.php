<?php
declare(strict_types=1);

use App\Contracts\Repositories\PostRepository;
use App\Contracts\Services\AuditLogger;
use App\Services\PostService;

final class InMemoryPostRepository implements PostRepository
{
    public array $posts = [];
    public array $deleted = [];

    public function findActiveById(int $id): ?array
    {
        return $this->posts[$id] ?? null;
    }

    public function softDelete(int $id): void
    {
        $this->deleted[] = $id;
        unset($this->posts[$id]);
    }
}

final class SpyAuditLogger implements AuditLogger
{
    public array $records = [];

    public function record(string $action, ?string $entity = null, ?int $entityId = null, array $details = []): void
    {
        $this->records[] = compact('action', 'entity', 'entityId', 'details');
    }
}

return static function (): void {
    $repo = new InMemoryPostRepository();
    $audit = new SpyAuditLogger();
    $repo->posts[10] = ['id' => 10, 'autor_id' => 5, 'autor_perfil' => 'secretaria', 'titulo' => 'Aviso', 'status' => 'publicado'];

    $service = new PostService($repo, $audit);
    $service->deletePost(10, 9, 'secretaria');

    if ($repo->deleted !== [10]) throw new RuntimeException('Soft delete não foi chamado.');
    if (($audit->records[0]['action'] ?? null) !== 'excluir_post') throw new RuntimeException('Auditoria não foi registrada.');

    $repo = new InMemoryPostRepository();
    $audit = new SpyAuditLogger();
    $repo->posts[11] = ['id' => 11, 'autor_id' => 1, 'autor_perfil' => 'admin', 'titulo' => 'Admin', 'status' => 'publicado'];
    $service = new PostService($repo, $audit);

    $blocked = false;
    try {
        $service->deletePost(11, 9, 'secretaria');
    } catch (RuntimeException) {
        $blocked = true;
    }

    if ($blocked !== true) throw new RuntimeException('Secretaria excluiu post não permitido.');
    if ($repo->deleted !== []) throw new RuntimeException('Post bloqueado foi excluído.');
};
