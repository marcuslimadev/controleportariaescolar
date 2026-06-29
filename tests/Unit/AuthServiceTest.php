<?php
declare(strict_types=1);

use App\Contracts\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\PasswordService;

final class InMemoryUserRepository implements UserRepository
{
    public array $users = [];
    public array $updated = [];

    public function findActiveByEmail(string $email): ?array
    {
        return $this->users[strtolower($email)] ?? null;
    }

    public function findActiveById(int $id): ?array
    {
        foreach ($this->users as $user) {
            if ((int)($user['id'] ?? 0) === $id) return $user;
        }
        return null;
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $this->updated[$id] = $hash;
    }

    public function updatePhoto(int $id, string $photoUrl): void {}

    public function updateProfile(int $id, string $name, ?string $bio): void {}
}

return static function (): void {
    $users = new InMemoryUserRepository();
    $guardians = new InMemoryGuardianRepository();
    $users->users['admin@teste.local'] = [
        'id' => 1,
        'nome' => 'Admin',
        'email' => 'admin@teste.local',
        'perfil' => 'admin',
        'senha_hash' => PasswordService::hash('Senha@12345'),
    ];

    $service = new AuthService($users, $guardians);
    $staff = $service->authenticate('admin@teste.local', 'Senha@12345');
    if (($staff['type'] ?? null) !== 'user') throw new RuntimeException('Login de usuário falhou.');
    if (($staff['home'] ?? null) !== 'feed.php') throw new RuntimeException('Home de usuário incorreta.');

    $guardian = $service->authenticate('111.444.777-35', 'Senha@12345');
    if (($guardian['type'] ?? null) !== 'guardian') throw new RuntimeException('Login de responsável falhou.');

    $invalid = $service->authenticate('admin@teste.local', 'errada');
    if ($invalid !== null) throw new RuntimeException('Senha inválida autenticou.');
};
