<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface PostInteractionRepository
{
    public function canInteract(int $postId, string $visibilitySql, array $visibilityParams, bool $requiresScience = false): bool;

    public function toggleLike(int $postId, ?int $guardianId, ?int $userId): void;

    public function confirmScience(int $postId, ?int $guardianId, ?int $userId, ?string $ip, string $userAgent): void;
}
