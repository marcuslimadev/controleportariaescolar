<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\PostRepository;
use App\Contracts\Services\AuditLogger;
use RuntimeException;

final class PostService
{
    private const TYPES = ['comunicado','atividade','evento','programação','alerta','cardápio','lembrete'];
    private const SCOPES = ['toda_escola','turma','aluno','equipe'];
    private const STATUSES = ['rascunho','publicado','arquivado'];

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

    public function savePost(array $input, ?string $imageUrl, int $actorId, string $actorRole): int
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

        $data = $this->normalizePostData($input, $imageUrl ?: ($current['imagem_url'] ?? null), $actorId, $current);
        if ($id > 0) {
            $this->posts->update($id, $data);
            $this->audit->record('editar_post', 'scp_posts', $id);

            return $id;
        }

        $id = $this->posts->create($data);
        $this->audit->record('criar_post', 'scp_posts', $id);

        return $id;
    }

    private function normalizePostData(array $input, ?string $imageUrl, int $actorId, ?array $current): array
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
