<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\StudentRepository;
use PDO;

final class PdoStudentRepository implements StudentRepository
{
    public function __construct(private PDO $pdo) {}

    public function activeExistsByQrToken(string $token): bool
    {
        $query = $this->pdo->prepare('SELECT id FROM scp_alunos WHERE qr_token=? AND ativo=1 LIMIT 1');
        $query->execute([$token]);

        return (bool)$query->fetchColumn();
    }
}
