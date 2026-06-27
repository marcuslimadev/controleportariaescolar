<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\NotificationRepository;

final class NotificationService
{
    public function __construct(private NotificationRepository $notifications) {}

    public function notifyPostPublished(int $postId, array $postData, int $actorId): void
    {
        $this->notifications->notifyPostPublished($postId, $postData, $actorId);
    }

    public function unreadCount(?int $userId, ?int $guardianId): int
    {
        if (!$userId && !$guardianId) return 0;
        return $this->notifications->unreadCount($userId, $guardianId);
    }

    public function listForActor(?int $userId, ?int $guardianId): array
    {
        if (!$userId && !$guardianId) return [];
        return $this->notifications->listForActor($userId, $guardianId);
    }

    public function markAllRead(?int $userId, ?int $guardianId): void
    {
        if (!$userId && !$guardianId) return;
        $this->notifications->markAllRead($userId, $guardianId);
    }
}
