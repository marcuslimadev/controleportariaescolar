<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface NotificationRepository
{
    public function notifyPostPublished(int $postId, array $postData, int $actorId): void;

    public function unreadCount(?int $userId, ?int $guardianId): int;

    public function listForActor(?int $userId, ?int $guardianId): array;

    public function markAllRead(?int $userId, ?int $guardianId): void;
}
