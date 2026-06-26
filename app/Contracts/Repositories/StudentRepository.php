<?php
declare(strict_types=1);

namespace App\Contracts\Repositories;

interface StudentRepository
{
    public function activeExistsByQrToken(string $token): bool;
}
