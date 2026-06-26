<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\AccessLogRepository;
use PDO;

final class PdoAccessLogRepository implements AccessLogRepository
{
    public function __construct(private PDO $pdo) {}

    public function record(
        int $studentId,
        int $guardianId,
        string $type,
        int $operatorId,
        string $origin,
        ?string $note,
        bool $manual,
        ?string $ip
    ): void {
        $query = $this->pdo->prepare(
            'INSERT INTO scp_registros_acesso(aluno_id,responsavel_id,tipo,usuario_id,origem,observacao,correcao_manual,ip) VALUES(?,?,?,?,?,?,?,?)'
        );
        $query->execute([$studentId, $guardianId, $type, $operatorId, $origin, $note, $manual ? 1 : 0, $ip]);
    }
}
