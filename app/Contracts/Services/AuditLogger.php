<?php
declare(strict_types=1);

namespace App\Contracts\Services;

interface AuditLogger
{
    public function record(string $action, ?string $entity = null, ?int $entityId = null, array $details = []): void;
}
