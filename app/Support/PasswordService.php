<?php
declare(strict_types=1);

namespace App\Support;

final class PasswordService
{
    private const OPTIONS = [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 2,
    ];

    public static function hash(string $plain): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        $options = $algo === PASSWORD_ARGON2ID ? self::OPTIONS : [];

        return password_hash($plain, $algo, $options);
    }

    public static function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        $options = $algo === PASSWORD_ARGON2ID ? self::OPTIONS : [];

        return password_needs_rehash($hash, $algo, $options);
    }
}
