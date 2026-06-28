<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface AccessLogRepository
{
    public function record(
        int $studentId,
        int $guardianId,
        string $type,
        int $operatorId,
        string $origin,
        ?string $note,
        bool $manual,
        ?string $ip,
        ?string $clientUid = null
    ): void;

    public function clientUidExists(string $clientUid): bool;
}
