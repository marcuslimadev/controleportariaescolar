<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface SchoolAdminRepository
{
    public function createClass(string $name, string $shift): int;
    public function createStudent(array $data, string $qrToken): int;
    public function createGuardian(array $data, string $passwordHash): int;
    public function linkGuardian(array $data): void;
    public function createUser(array $data, string $passwordHash): int;
    public function createTeacher(array $data): int;
    public function linkTeacherClass(int $teacherId, int $classId): void;
    public function toggleActive(string $table, int $id): void;
    public function saveSetting(string $key, string $value): void;
    public function dashboardData(): array;
}
