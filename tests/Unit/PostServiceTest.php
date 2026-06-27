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
    public array $adminList = [];
    public array $feedList = [];
    public array $publicList = [];
    public array $eventList = [];
    public array $classes = [['id' => 1, 'nome' => '1A']];
    public array $students = [['id' => 2, 'nome' => 'Aluno']];

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

    public function listAdmin(int $limit = 100): array { return $this->adminList; }

    public function scienceHistory(int $postId): array { return []; }

    public function feed(string $visibilitySql, array $visibilityParams, int $actorId, bool $isGuardian, int $limit = 80): array
    {
        return $this->feedList ?: [['actor_id' => $actorId, 'is_guardian' => $isGuardian]];
    }

    public function publicFeed(int $limit = 6): array { return $this->publicList; }

    public function events(string $start, string $end, string $visibilitySql, array $visibilityParams, ?int $classId): array
    {
        return $this->eventList ?: [['start' => $start, 'end' => $end, 'class_id' => $classId]];
    }

    public function activeClasses(): array { return $this->classes; }

    public function activeStudents(): array { return $this->students; }
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
    ], null, null, null, 7, 'admin');
    if ($createdId !== 99) throw new RuntimeException('Criação não retornou id.');
    if (($repo->created[0]['publicado_em'] ?? null) === null) throw new RuntimeException('Publicado sem data de publicação.');
    if (($audit->records[0]['action'] ?? null) !== 'criar_post') throw new RuntimeException('Criação não auditou.');

    $repo = new InMemoryPostRepository();
    $audit = new SpyAuditLogger();
    $repo->posts[20] = ['id' => 20, 'autor_id' => 7, 'autor_perfil' => 'secretaria', 'imagem_url' => 'old.jpg', 'anexo_url' => 'old.pdf', 'anexo_nome' => 'old.pdf', 'publicado_em' => null];
    $service = new PostService($repo, $audit);
    $updatedId = $service->savePost([
        'id' => 20,
        'titulo' => 'Atualizado',
        'conteudo' => 'Texto atualizado',
        'tipo' => 'alerta',
        'publico' => 'toda_escola',
        'status' => 'rascunho',
    ], null, null, null, 7, 'secretaria');
    if ($updatedId !== 20) throw new RuntimeException('Edição não retornou id original.');
    if (($repo->updated[20]['imagem_url'] ?? null) !== 'old.jpg') throw new RuntimeException('Imagem antiga não foi preservada.');
    if (($repo->updated[20]['anexo_url'] ?? null) !== 'old.pdf') throw new RuntimeException('Anexo antigo não foi preservado.');
    if (($audit->records[0]['action'] ?? null) !== 'editar_post') throw new RuntimeException('Edição não auditou.');

    $form = $service->formData(20, 7, 'secretaria');
    if (($form['post']['id'] ?? null) !== 20) throw new RuntimeException('Formulário não carregou post.');
    if (($form['classes'][0]['nome'] ?? null) !== '1A') throw new RuntimeException('Turmas do formulário não carregaram.');

    $feed = $service->feedPosts('1=1', [], 7, true);
    if (($feed[0]['is_guardian'] ?? null) !== true) throw new RuntimeException('Feed não repassou ator responsável.');

    $events = $service->eventsForMonth('2026-06', '1=1', [], 1);
    if (($events['month'] ?? null) !== '2026-06') throw new RuntimeException('Mês de eventos não foi preservado.');
};
