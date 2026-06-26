<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\GuardianRepository;
use App\Contracts\Repositories\UserRepository;
use App\Support\PasswordService;

final class AuthService
{
    public function __construct(
        private UserRepository $users,
        private GuardianRepository $guardians,
    ) {}

    public function authenticate(string $login, string $password): ?array
    {
        $login = trim($login);
        $staff = $this->authenticateStaff($login, $password);
        if ($staff) {
            return $staff;
        }

        $digits = preg_replace('/\D+/', '', $login) ?: '';
        if ($digits === '') {
            return null;
        }

        return $this->authenticateGuardian($digits, $password);
    }

    private function authenticateStaff(string $login, string $password): ?array
    {
        $user = $this->users->findActiveByEmail($login);
        if (!$user || !PasswordService::verify($password, (string)$user['senha_hash'])) {
            return null;
        }

        if (PasswordService::needsRehash((string)$user['senha_hash'])) {
            $this->users->updatePasswordHash((int)$user['id'], PasswordService::hash($password));
        }

        return [
            'type' => 'user',
            'id' => (int)$user['id'],
            'name' => $user['nome'],
            'role' => $user['perfil'],
            'home' => $user['perfil'] === 'portaria' ? 'portaria/index.php' : 'feed.php',
            'audit' => 'login_usuario',
        ];
    }

    private function authenticateGuardian(string $digits, string $password): ?array
    {
        $guardian = $this->guardians->findActiveByCpfOrPhone($digits);
        if (!$guardian || !PasswordService::verify($password, (string)$guardian['senha_hash'])) {
            return null;
        }

        if (PasswordService::needsRehash((string)$guardian['senha_hash'])) {
            $this->guardians->updatePasswordHash((int)$guardian['id'], PasswordService::hash($password));
        }

        return [
            'type' => 'guardian',
            'id' => (int)$guardian['id'],
            'name' => $guardian['nome'],
            'home' => 'feed.php',
            'audit' => 'login_responsavel',
        ];
    }
}
