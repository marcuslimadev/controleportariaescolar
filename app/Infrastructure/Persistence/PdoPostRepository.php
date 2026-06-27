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
            'SELECT p.*, u.perfil autor_perfil, u.foto autor_foto FROM scp_posts p JOIN scp_usuarios u ON u.id=p.autor_id WHERE p.id=? AND p.deleted_at IS NULL'
        );
        $query->execute([$id]);
        $post = $query->fetch(PDO::FETCH_ASSOC);

        return $post ?: null;
    }

    public function listAdmin(int $limit = 100): array
    {
        $limit = max(1, min(200, $limit));
        $query = $this->pdo->query(
            "SELECT p.*, u.nome autor, t.nome turma, a.nome aluno,
                (SELECT COUNT(*) FROM scp_post_ciencias ci WHERE ci.post_id=p.id) ciencia_total
             FROM scp_posts p
             JOIN scp_usuarios u ON u.id=p.autor_id
             LEFT JOIN scp_turmas t ON t.id=p.turma_id
             LEFT JOIN scp_alunos a ON a.id=p.aluno_id
             WHERE p.deleted_at IS NULL
             ORDER BY p.created_at DESC
             LIMIT {$limit}"
        );

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function scienceHistory(int $postId): array
    {
        $query = $this->pdo->prepare(
            "SELECT ci.confirmado_em, ci.ip, ci.user_agent,
                    COALESCE(r.nome, u.nome) pessoa,
                    CASE WHEN ci.responsavel_id IS NOT NULL THEN 'responsavel' ELSE u.perfil END perfil
             FROM scp_post_ciencias ci
             LEFT JOIN scp_responsaveis r ON r.id=ci.responsavel_id
             LEFT JOIN scp_usuarios u ON u.id=ci.usuario_id
             WHERE ci.post_id=?
             ORDER BY ci.confirmado_em DESC"
        );
        $query->execute([$postId]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function feed(string $visibilitySql, array $visibilityParams, int $actorId, bool $isGuardian, int $limit = 80): array
    {
        $limit = max(1, min(120, $limit));
        $actorColumn = $isGuardian ? 'c.responsavel_id=?' : 'c.usuario_id=?';
        $scienceColumn = $isGuardian ? 'ci.responsavel_id=?' : 'ci.usuario_id=?';
        $params = array_merge([$actorId, $actorId], $visibilityParams);
        $query = $this->pdo->prepare(
            "SELECT p.*, u.nome autor, u.foto autor_foto,
                (SELECT COUNT(*) FROM scp_post_curtidas c WHERE c.post_id=p.id) curtidas,
                (SELECT COUNT(*) FROM scp_post_curtidas c WHERE c.post_id=p.id AND {$actorColumn}) curtiu,
                (SELECT confirmado_em FROM scp_post_ciencias ci WHERE ci.post_id=p.id AND {$scienceColumn} LIMIT 1) ciencia_em
             FROM scp_posts p
             JOIN scp_usuarios u ON u.id=p.autor_id
             WHERE p.status='publicado' AND p.deleted_at IS NULL AND {$visibilitySql}
             ORDER BY p.fixado DESC, p.publicado_em DESC, p.id DESC
             LIMIT {$limit}"
        );
        $query->execute($params);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function publicFeed(int $limit = 6): array
    {
        $limit = max(1, min(20, $limit));
        $query = $this->pdo->query(
            "SELECT p.tipo,p.titulo,p.conteudo,p.imagem_url,p.anexo_url,p.anexo_nome,p.data_evento,p.hora_evento,p.local,p.importante,p.fixado,p.publicado_em,u.nome autor,u.foto autor_foto
             FROM scp_posts p
             JOIN scp_usuarios u ON u.id=p.autor_id
             WHERE p.status='publicado' AND p.publico='publico' AND p.deleted_at IS NULL
             ORDER BY p.fixado DESC, p.publicado_em DESC, p.id DESC
             LIMIT {$limit}"
        );

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function publicGallery(int $limit = 60): array
    {
        $limit = max(1, min(100, $limit));
        $query = $this->pdo->query(
            "SELECT p.tipo,p.titulo,p.conteudo,p.imagem_url,p.publicado_em,u.nome autor
             FROM scp_posts p
             JOIN scp_usuarios u ON u.id=p.autor_id
             WHERE p.status='publicado' AND p.publico='publico' AND p.imagem_url IS NOT NULL AND p.imagem_url<>'' AND p.deleted_at IS NULL
             ORDER BY p.publicado_em DESC, p.id DESC
             LIMIT {$limit}"
        );

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function events(string $start, string $end, string $visibilitySql, array $visibilityParams, ?int $classId): array
    {
        $params = array_merge([$start, $end], $visibilityParams);
        $extra = '';
        if ($classId !== null && $classId > 0) {
            $extra = ' AND (p.turma_id=? OR p.turma_id IS NULL)';
            $params[] = $classId;
        }
        $query = $this->pdo->prepare(
            "SELECT p.*, u.nome autor, t.nome turma
             FROM scp_posts p
             JOIN scp_usuarios u ON u.id=p.autor_id
             LEFT JOIN scp_turmas t ON t.id=p.turma_id
             WHERE p.status='publicado' AND p.deleted_at IS NULL
               AND p.tipo IN ('evento','programação')
               AND p.data_evento BETWEEN ? AND ?
               AND {$visibilitySql} {$extra}
             ORDER BY p.data_evento ASC, p.hora_evento ASC"
        );
        $query->execute($params);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeClasses(): array
    {
        return $this->pdo->query('SELECT id,nome FROM scp_turmas WHERE ativo=1 ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeStudents(): array
    {
        return $this->pdo->query('SELECT id,nome FROM scp_alunos WHERE ativo=1 AND deleted_at IS NULL ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $query = $this->pdo->prepare(
            'INSERT INTO scp_posts(autor_id,tipo,titulo,conteudo,imagem_url,anexo_url,anexo_nome,publico,turma_id,aluno_id,data_evento,hora_evento,local,importante,exige_ciencia,fixado,status,publicado_em) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $query->execute([
            $data['autor_id'],
            $data['tipo'],
            $data['titulo'],
            $data['conteudo'],
            $data['imagem_url'],
            $data['anexo_url'],
            $data['anexo_nome'],
            $data['publico'],
            $data['turma_id'],
            $data['aluno_id'],
            $data['data_evento'],
            $data['hora_evento'],
            $data['local'],
            $data['importante'] ? 1 : 0,
            $data['exige_ciencia'] ? 1 : 0,
            $data['fixado'] ? 1 : 0,
            $data['status'],
            $data['publicado_em'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $query = $this->pdo->prepare(
            'UPDATE scp_posts SET tipo=?,titulo=?,conteudo=?,imagem_url=?,anexo_url=?,anexo_nome=?,publico=?,turma_id=?,aluno_id=?,data_evento=?,hora_evento=?,local=?,importante=?,exige_ciencia=?,fixado=?,status=?,publicado_em=? WHERE id=?'
        );
        $query->execute([
            $data['tipo'],
            $data['titulo'],
            $data['conteudo'],
            $data['imagem_url'],
            $data['anexo_url'],
            $data['anexo_nome'],
            $data['publico'],
            $data['turma_id'],
            $data['aluno_id'],
            $data['data_evento'],
            $data['hora_evento'],
            $data['local'],
            $data['importante'] ? 1 : 0,
            $data['exige_ciencia'] ? 1 : 0,
            $data['fixado'] ? 1 : 0,
            $data['status'],
            $data['publicado_em'],
            $id,
        ]);
    }

    public function softDelete(int $id): void
    {
        $query = $this->pdo->prepare("UPDATE scp_posts SET deleted_at=NOW(), status='arquivado', fixado=0 WHERE id=?");
        $query->execute([$id]);
    }
}
