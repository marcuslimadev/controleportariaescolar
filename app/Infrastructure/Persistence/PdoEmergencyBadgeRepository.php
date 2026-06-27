<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\EmergencyBadgeRepository;
use PDO;

final class PdoEmergencyBadgeRepository implements EmergencyBadgeRepository
{
    public function __construct(private PDO $pdo) {}

    public function findActiveStudentByToken(string $token): ?array
    {
        $query = $this->pdo->prepare('SELECT id,qr_token FROM scp_alunos WHERE qr_token=? AND ativo=1 AND deleted_at IS NULL LIMIT 1');
        $query->execute([$token]);
        $student = $query->fetch(PDO::FETCH_ASSOC);

        return $student ?: null;
    }

    public function guardianCanConsult(int $studentId, int $guardianId): bool
    {
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM scp_aluno_responsavel WHERE aluno_id=? AND responsavel_id=? AND autoriza_consulta=1');
        $query->execute([$studentId, $guardianId]);

        return (int)$query->fetchColumn() > 0;
    }

    public function createAlert(array $data): int
    {
        $query = $this->pdo->prepare(
            'INSERT INTO scp_alertas_cracha(aluno_id,qr_token,nome_informante,telefone_informante,mensagem,latitude,longitude,ip,user_agent)
             VALUES(?,?,?,?,?,?,?,?,?)'
        );
        $query->execute([
            $data['aluno_id'],
            $data['qr_token'],
            $data['nome_informante'],
            $data['telefone_informante'],
            $data['mensagem'],
            $data['latitude'],
            $data['longitude'],
            $data['ip'],
            $data['user_agent'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
