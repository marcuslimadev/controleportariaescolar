<?php
declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Contracts\Services\AuditLogger;
use PDO;

final class DatabaseAuditLogger implements AuditLogger
{
    public function __construct(private ?PDO $pdo = null) {}

    public function record(string $action, ?string $entity = null, ?int $entityId = null, array $details = []): void
    {
        $pdo = $this->pdo ?? \db();
        $statement = $pdo->prepare(
            'INSERT INTO scp_logs_auditoria (usuario_id, responsavel_id, acao, entidade, entidade_id, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $_SESSION['user_id'] ?? null,
            $_SESSION['responsavel_id'] ?? null,
            $action,
            $entity,
            $entityId,
            json_encode($details, JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    }
}
