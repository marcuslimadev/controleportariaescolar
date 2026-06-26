<?php
declare(strict_types=1);

namespace App\Support;

final class Permission
{
    private const MAP = [
        'admin' => ['*'],
        'secretaria' => [
            'post.manage',
            'absence.manage',
            'student.manage',
            'teacher.read',
            'access.read',
            'access.write',
            'badge.issue',
            'invite.manage',
            'frequency.manage',
        ],
        'portaria' => [
            'access.read',
            'access.write',
            'badge.issue',
            'invite.manage',
            'student.quick_create',
        ],
        'professor' => [
            'frequency.manage',
            'absence.read',
            'post.read',
        ],
        'responsavel' => [
            'child.read',
            'badge.read',
            'absence.create',
            'post.read',
        ],
    ];

    public static function roleHas(string $role, string $permission): bool
    {
        $permissions = self::MAP[$role] ?? [];

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }
}
