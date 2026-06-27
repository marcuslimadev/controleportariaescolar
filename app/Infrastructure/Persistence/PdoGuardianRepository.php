<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\GuardianRepository;
use PDO;

final class PdoGuardianRepository implements GuardianRepository
{
    public function __construct(private PDO $pdo) {}

    public function findActiveIdByQrToken(string $token): ?int
    {
        $query = $this->pdo->prepare('SELECT id FROM scp_responsaveis WHERE qr_token=? AND ativo=1 AND deleted_at IS NULL');
        $query->execute([$token]);
        $id = (int)$query->fetchColumn();

        return $id > 0 ? $id : null;
    }

    public function findActiveByQrToken(string $token): ?array
    {
        $query = $this->pdo->prepare('SELECT id,nome,foto,qr_token FROM scp_responsaveis WHERE qr_token=? AND ativo=1 AND deleted_at IS NULL');
        $query->execute([$token]);
        $guardian = $query->fetch(PDO::FETCH_ASSOC);

        return $guardian ?: null;
    }

    public function authorizedChildrenForWithdrawal(int $guardianId): array
    {
        $query = $this->pdo->prepare(
            "SELECT a.id,a.nome,a.foto,t.nome turma,
                (SELECT tipo FROM scp_registros_acesso r WHERE r.aluno_id=a.id ORDER BY r.registrado_em DESC,r.id DESC LIMIT 1) ultimo
             FROM scp_alunos a
             JOIN scp_aluno_responsavel ar ON ar.aluno_id=a.id
             LEFT JOIN scp_turmas t ON t.id=a.turma_id
             WHERE ar.responsavel_id=? AND ar.autoriza_retirada=1 AND a.ativo=1 AND a.deleted_at IS NULL
             ORDER BY a.nome"
        );
        $query->execute([$guardianId]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function canWithdrawStudent(int $guardianId, int $studentId): bool
    {
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM scp_aluno_responsavel WHERE aluno_id=? AND responsavel_id=? AND autoriza_retirada=1');
        $query->execute([$studentId, $guardianId]);

        return (int)$query->fetchColumn() > 0;
    }

    public function findIdByCpf(string $cpf): ?int
    {
        $query = $this->pdo->prepare('SELECT id FROM scp_responsaveis WHERE cpf=? AND deleted_at IS NULL LIMIT 1');
        $query->execute([$cpf]);
        $id = (int)$query->fetchColumn();

        return $id > 0 ? $id : null;
    }

    public function findActiveByCpfOrPhone(string $digits): ?array
    {
        $query = $this->pdo->prepare("SELECT * FROM scp_responsaveis WHERE ativo=1 AND deleted_at IS NULL AND (cpf=? OR REPLACE(REPLACE(REPLACE(REPLACE(telefone,' ',''),'-',''),'(',''),')','')=?) LIMIT 1");
        $query->execute([$digits, $digits]);
        $guardian = $query->fetch(PDO::FETCH_ASSOC);

        return $guardian ?: null;
    }

    public function findActiveById(int $id): ?array
    {
        $query = $this->pdo->prepare('SELECT * FROM scp_responsaveis WHERE id=? AND ativo=1 AND deleted_at IS NULL LIMIT 1');
        $query->execute([$id]);
        $guardian = $query->fetch(PDO::FETCH_ASSOC);

        return $guardian ?: null;
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $query = $this->pdo->prepare('UPDATE scp_responsaveis SET senha_hash=? WHERE id=?');
        $query->execute([$hash, $id]);
    }

    public function updatePhoto(int $id, string $photoUrl): void
    {
        $query = $this->pdo->prepare('UPDATE scp_responsaveis SET foto=? WHERE id=?');
        $query->execute([$photoUrl, $id]);
    }

    public function createFromInvite(array $invite): int
    {
        $query = $this->pdo->prepare('INSERT INTO scp_responsaveis(nome,cpf,email,telefone,foto,senha_hash) VALUES(?,?,?,?,?,?)');
        $query->execute([
            $invite['responsavel_nome'],
            $invite['responsavel_cpf'],
            $invite['responsavel_email'] ?: null,
            $invite['telefone'],
            $invite['responsavel_foto'],
            $invite['senha_hash'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateFromInvite(int $guardianId, array $invite): void
    {
        $query = $this->pdo->prepare('UPDATE scp_responsaveis SET nome=?,email=?,telefone=?,foto=?,senha_hash=?,ativo=1 WHERE id=?');
        $query->execute([
            $invite['responsavel_nome'],
            $invite['responsavel_email'] ?: null,
            $invite['telefone'],
            $invite['responsavel_foto'],
            $invite['senha_hash'],
            $guardianId,
        ]);
    }
}
