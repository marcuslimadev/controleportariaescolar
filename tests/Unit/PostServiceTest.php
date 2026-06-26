<?php
declare(strict_types=1);

use App\Contracts\Repositories\PostRepository;
use App\Contracts\Services\AuditLogger;
use App\Services\PostService;

final class InMemoryPostRepository implements PostRepository
{
    public array $posts = [];
    public array $deleted = [];
    public array $created = [];
    public array $updated = [];

    public function findActiveById(int $id): ?array
    {
        return $this->posts[$id] ?? null;
    }

    public function create(array $data): int
    {
        $this->created[] = $data;
        return 99;
    }

    public function update(int $id, array $data): void
    {
        $this->updated[$id] = $data;
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

    $repo = new InMemoryPostRepository();
    $audit = new SpyAuditLogger();
    $service = new PostService($repo, $audit);
    $createdId = $service->savePost([
        'titulo' => 'Comunicado',
        'conteudo' => 'Texto oficial',
        'tipo' => 'comunicado',
        'publico' => 'toda_escola',
        'status' => 'publicado',
    ], null, 7, 'admin');
    if ($createdId !== 99) throw new RuntimeException('Criação não retornou id.');
    if (($repo->created[0]['publicado_em'] ?? null) === null) throw new RuntimeException('Publicado sem data de publicação.');
    if (($audit->records[0]['action'] ?? null) !== 'criar_post') throw new RuntimeException('Criação não auditou.');

    $repo = new InMemoryPostRepository();
    $audit = new SpyAuditLogger();
    $repo->posts[20] = ['id' => 20, 'autor_id' => 7, 'autor_perfil' => 'secretaria', 'imagem_url' => 'old.jpg', 'publicado_em' => null];
    $service = new PostService($repo, $audit);
    $updatedId = $service->savePost([
        'id' => 20,
        'titulo' => 'Atualizado',
        'conteudo' => 'Texto atualizado',
        'tipo' => 'alerta',
        'publico' => 'toda_escola',
        'status' => 'rascunho',
    ], null, 7, 'secretaria');
    if ($updatedId !== 20) throw new RuntimeException('Edição não retornou id original.');
    if (($repo->updated[20]['imagem_url'] ?? null) !== 'old.jpg') throw new RuntimeException('Imagem antiga não foi preservada.');
    if (($audit->records[0]['action'] ?? null) !== 'editar_post') throw new RuntimeException('Edição não auditou.');
};
