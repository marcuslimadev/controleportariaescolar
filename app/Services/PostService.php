<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\PostRepository;
use App\Contracts\Services\AuditLogger;
use RuntimeException;

final class PostService
{
    private const TYPES = ['comunicado','atividade','evento','programação','alerta','cardápio','lembrete'];
    private const SCOPES = ['publico','toda_escola','turma','aluno','equipe'];
    private const STATUSES = ['rascunho','publicado','arquivado'];

    public function __construct(
        private PostRepository $posts,
        private AuditLogger $audit,
        private ?NotificationService $notifications = null,
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

    public function listAdminPosts(): array
    {
        return $this->posts->listAdmin(100);
    }

    public function publicPosts(int $limit = 6): array
    {
        return $this->posts->publicFeed($limit);
    }

    public function publicGallery(int $limit = 60): array
    {
        return $this->posts->publicGallery($limit);
    }

    public function feedPosts(string $visibilitySql, array $visibilityParams, int $actorId, bool $isGuardian): array
    {
        return $this->posts->feed($visibilitySql, $visibilityParams, $actorId, $isGuardian, 80);
    }

    public function eventsForMonth(string $month, string $visibilitySql, array $visibilityParams, ?int $classId): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));

        return [
            'month' => $month,
            'events' => $this->posts->events($start, $end, $visibilitySql, $visibilityParams, $classId),
            'classes' => $this->posts->activeClasses(),
        ];
    }

    public function formData(int $id, int $actorId, string $actorRole): array
    {
        $post = [
            'id'=>0,'tipo'=>'comunicado','titulo'=>'','conteudo'=>'','imagem_url'=>'','anexo_url'=>'','anexo_nome'=>'','publico'=>'publico',
            'turma_id'=>'','aluno_id'=>'','data_evento'=>'','hora_evento'=>'','local'=>'',
            'importante'=>0,'exige_ciencia'=>0,'fixado'=>0,'status'=>'rascunho',
        ];
        if ($id > 0) {
            $loaded = $this->posts->findActiveById($id);
            if (!$loaded) {
                throw new RuntimeException('Publicação não encontrada.');
            }
            $this->assertCanManageExistingPost($loaded, $actorId, $actorRole, 'editar');
            $post = $loaded;
        }

        return [
            'post' => $post,
            'classes' => $this->posts->activeClasses(),
            'students' => $this->posts->activeStudents(),
        ];
    }

    public function savePost(array $input, ?string $imageUrl, ?string $attachmentUrl, ?string $attachmentName, int $actorId, string $actorRole): int
    {
        $id = (int)($input['id'] ?? 0);
        $current = null;
        if ($id > 0) {
            $current = $this->posts->findActiveById($id);
            if (!$current) {
                throw new RuntimeException('Publicação não encontrada.');
            }
            $this->assertCanManageExistingPost($current, $actorId, $actorRole, 'editar');
        }

        $data = $this->normalizePostData(
            $input,
            $imageUrl ?: ($current['imagem_url'] ?? null),
            $attachmentUrl ?: ($current['anexo_url'] ?? null),
            $attachmentName ?: ($current['anexo_nome'] ?? null),
            $actorId,
            $current
        );
        if ($id > 0) {
            $this->posts->update($id, $data);
            $this->audit->record('editar_post', 'scp_posts', $id);
            if (($current['status'] ?? '') !== 'publicado' && $data['status'] === 'publicado') {
                $this->notifications?->notifyPostPublished($id, $data, $actorId);
            }

            return $id;
        }

        $id = $this->posts->create($data);
        $this->audit->record('criar_post', 'scp_posts', $id);
        if ($data['status'] === 'publicado') {
            $this->notifications?->notifyPostPublished($id, $data, $actorId);
        }

        return $id;
    }

    public function scienceHistory(int $postId, int $actorId, string $actorRole): array
    {
        $post = $this->posts->findActiveById($postId);
        if (!$post) {
            throw new RuntimeException('Publicação não encontrada.');
        }
        $this->assertCanManageExistingPost($post, $actorId, $actorRole, 'ver');

        return ['post' => $post, 'rows' => $this->posts->scienceHistory($postId)];
    }

    private function normalizePostData(array $input, ?string $imageUrl, ?string $attachmentUrl, ?string $attachmentName, int $actorId, ?array $current): array
    {
        $type = in_array($input['tipo'] ?? '', self::TYPES, true) ? (string)$input['tipo'] : 'comunicado';
        $scope = in_array($input['publico'] ?? '', self::SCOPES, true) ? (string)$input['publico'] : 'toda_escola';
        $status = in_array($input['status'] ?? '', self::STATUSES, true) ? (string)$input['status'] : 'rascunho';
        $title = trim((string)($input['titulo'] ?? ''));
        $body = trim((string)($input['conteudo'] ?? ''));
        if ($title === '' || $body === '') {
            throw new RuntimeException('Informe título e conteúdo.');
        }

        $classId = ($input['turma_id'] ?? '') !== '' ? (int)$input['turma_id'] : null;
        $studentId = ($input['aluno_id'] ?? '') !== '' ? (int)$input['aluno_id'] : null;
        if ($scope === 'turma' && !$classId) throw new RuntimeException('Selecione a turma.');
        if ($scope === 'aluno' && !$studentId) throw new RuntimeException('Selecione o aluno.');
        if ($scope !== 'turma') $classId = null;
        if ($scope !== 'aluno') $studentId = null;

        $publishedAt = null;
        if ($status === 'publicado') {
            $publishedAt = $current['publicado_em'] ?? date('Y-m-d H:i:s');
        }

        return [
            'autor_id' => $actorId,
            'tipo' => $type,
            'titulo' => $title,
            'conteudo' => $body,
            'imagem_url' => $imageUrl,
            'anexo_url' => $attachmentUrl,
            'anexo_nome' => $attachmentName,
            'publico' => $scope,
            'turma_id' => $classId,
            'aluno_id' => $studentId,
            'data_evento' => ($input['data_evento'] ?? '') !== '' ? $input['data_evento'] : null,
            'hora_evento' => ($input['hora_evento'] ?? '') !== '' ? $input['hora_evento'] : null,
            'local' => trim((string)($input['local'] ?? '')) ?: null,
            'importante' => !empty($input['importante']),
            'exige_ciencia' => !empty($input['exige_ciencia']),
            'fixado' => !empty($input['fixado']),
            'status' => $status,
            'publicado_em' => $publishedAt,
        ];
    }

    private function assertCanManageExistingPost(array $post, int $actorId, string $actorRole, string $verb): void
    {
        if ($actorRole === 'secretaria' && (int)$post['autor_id'] !== $actorId && ($post['autor_perfil'] ?? '') !== 'secretaria') {
            throw new RuntimeException("Você só pode {$verb} publicações da secretaria ou criadas por você.");
        }
    }
}
