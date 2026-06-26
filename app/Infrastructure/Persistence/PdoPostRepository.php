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
            'SELECT p.*, u.perfil autor_perfil FROM scp_posts p JOIN scp_usuarios u ON u.id=p.autor_id WHERE p.id=? AND p.deleted_at IS NULL'
        );
        $query->execute([$id]);
        $post = $query->fetch(PDO::FETCH_ASSOC);

        return $post ?: null;
    }

    public function create(array $data): int
    {
        $query = $this->pdo->prepare(
            'INSERT INTO scp_posts(autor_id,tipo,titulo,conteudo,imagem_url,publico,turma_id,aluno_id,data_evento,hora_evento,local,importante,exige_ciencia,fixado,status,publicado_em) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $query->execute([
            $data['autor_id'],
            $data['tipo'],
            $data['titulo'],
            $data['conteudo'],
            $data['imagem_url'],
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
            'UPDATE scp_posts SET tipo=?,titulo=?,conteudo=?,imagem_url=?,publico=?,turma_id=?,aluno_id=?,data_evento=?,hora_evento=?,local=?,importante=?,exige_ciencia=?,fixado=?,status=?,publicado_em=? WHERE id=?'
        );
        $query->execute([
            $data['tipo'],
            $data['titulo'],
            $data['conteudo'],
            $data['imagem_url'],
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
