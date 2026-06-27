<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\UserRepository;
use PDO;

final class PdoUserRepository implements UserRepository
{
    public function __construct(private PDO $pdo) {}

    public function findActiveByEmail(string $email): ?array
    {
        $query = $this->pdo->prepare('SELECT * FROM scp_usuarios WHERE email=? AND ativo=1 AND deleted_at IS NULL LIMIT 1');
        $query->execute([strtolower(trim($email))]);
        $user = $query->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findActiveById(int $id): ?array
    {
        $query = $this->pdo->prepare('SELECT * FROM scp_usuarios WHERE id=? AND ativo=1 AND deleted_at IS NULL LIMIT 1');
        $query->execute([$id]);
        $user = $query->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $query = $this->pdo->prepare('UPDATE scp_usuarios SET senha_hash=? WHERE id=?');
        $query->execute([$hash, $id]);
    }

    public function updatePhoto(int $id, string $photoUrl): void
    {
        $query = $this->pdo->prepare('UPDATE scp_usuarios SET foto=? WHERE id=?');
        $query->execute([$photoUrl, $id]);
    }
}
