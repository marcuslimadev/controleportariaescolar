<?php
declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Contracts\Services\AuditLogger;

final class DatabaseAuditLogger implements AuditLogger
{
    public function record(string $action, ?string $entity = null, ?int $entityId = null, array $details = []): void
    {
        \audit($action, $entity, $entityId, $details);
    }
}
