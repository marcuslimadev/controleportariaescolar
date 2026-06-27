<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface PostRepository
{
    public function findActiveById(int $id): ?array;

    public function listAdmin(int $limit = 100): array;

    public function scienceHistory(int $postId): array;

    public function feed(string $visibilitySql, array $visibilityParams, int $actorId, bool $isGuardian, int $limit = 80): array;

    public function publicFeed(int $limit = 6): array;

    public function publicGallery(int $limit = 60): array;

    public function events(string $start, string $end, string $visibilitySql, array $visibilityParams, ?int $classId): array;

    public function activeClasses(): array;

    public function activeStudents(): array;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function softDelete(int $id): void;
}
