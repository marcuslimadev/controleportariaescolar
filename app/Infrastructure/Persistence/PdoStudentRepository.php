<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\StudentRepository;
use PDO;

final class PdoStudentRepository implements StudentRepository
{
    public function __construct(private PDO $pdo) {}

    public function activeExistsByQrToken(string $token): bool
    {
        $query = $this->pdo->prepare('SELECT id FROM scp_alunos WHERE qr_token=? AND ativo=1 LIMIT 1');
        $query->execute([$token]);

        return (bool)$query->fetchColumn();
    }

    public function createFromInvite(array $invite, string $qrToken): int
    {
        $query = $this->pdo->prepare('INSERT INTO scp_alunos(nome,data_nascimento,foto,qr_token) VALUES(?,?,?,?)');
        $query->execute([
            $invite['aluno_nome'],
            $invite['aluno_data_nascimento'] ?: null,
            $invite['aluno_foto'],
            $qrToken,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function linkGuardian(int $studentId, int $guardianId): void
    {
        $query = $this->pdo->prepare(
            "INSERT INTO scp_aluno_responsavel(aluno_id,responsavel_id,parentesco,autoriza_consulta,autoriza_retirada) VALUES(?,?,'Responsável',1,1)"
        );
        $query->execute([$studentId, $guardianId]);
    }
}
