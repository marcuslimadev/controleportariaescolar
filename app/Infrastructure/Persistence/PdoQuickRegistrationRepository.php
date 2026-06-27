<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\QuickRegistrationRepository;
use PDO;
use Throwable;

final class PdoQuickRegistrationRepository implements QuickRegistrationRepository
{
    public function __construct(private PDO $pdo) {}

    public function activeClasses(): array
    {
        return $this->pdo->query('SELECT id,nome,turno FROM scp_turmas WHERE ativo=1 ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function classExists(int $classId): bool
    {
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM scp_turmas WHERE id=? AND ativo=1');
        $query->execute([$classId]);

        return (int)$query->fetchColumn() > 0;
    }

    public function create(array $data, string $studentQrToken, string $guardianPasswordHash): array
    {
        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare('SELECT id FROM scp_responsaveis WHERE cpf=? AND deleted_at IS NULL LIMIT 1');
            $query->execute([$data['responsavel_cpf']]);
            $guardianId = (int)$query->fetchColumn();

            if ($guardianId > 0) {
                $query = $this->pdo->prepare('UPDATE scp_responsaveis SET telefone=?,foto=COALESCE(?,foto),ativo=1 WHERE id=?');
                $query->execute([$data['responsavel_telefone'], $data['responsavel_foto'], $guardianId]);
            } else {
                $query = $this->pdo->prepare('INSERT INTO scp_responsaveis(nome,cpf,telefone,foto,senha_hash) VALUES(?,?,?,?,?)');
                $query->execute([
                    $data['responsavel_nome'],
                    $data['responsavel_cpf'],
                    $data['responsavel_telefone'],
                    $data['responsavel_foto'],
                    $guardianPasswordHash,
                ]);
                $guardianId = (int)$this->pdo->lastInsertId();
            }

            $query = $this->pdo->prepare('INSERT INTO scp_alunos(nome,cpf,data_nascimento,turma_id,foto,qr_token) VALUES(?,?,?,?,?,?)');
            $query->execute([
                $data['nome'],
                $data['cpf'],
                $data['data_nascimento'],
                $data['turma_id'],
                $data['foto'],
                $studentQrToken,
            ]);
            $studentId = (int)$this->pdo->lastInsertId();

            $query = $this->pdo->prepare('INSERT INTO scp_aluno_responsavel(aluno_id,responsavel_id,parentesco,autoriza_consulta,autoriza_retirada) VALUES(?,?,?,1,1)');
            $query->execute([$studentId, $guardianId, $data['parentesco']]);

            $this->pdo->commit();
            return ['aluno_id' => $studentId, 'responsavel_id' => $guardianId];
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }
}
