<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\GuardianRepository;
use App\Contracts\Repositories\UserRepository;
use App\Contracts\Services\AuditLogger;
use RuntimeException;

final class ProfileService
{
    public function __construct(
        private UserRepository $users,
        private GuardianRepository $guardians,
        private AuditLogger $audit,
    ) {}

    public function currentProfile(?int $userId, ?int $guardianId): array
    {
        if ($guardianId) {
            $profile = $this->guardians->findActiveById($guardianId);
            if (!$profile) throw new RuntimeException('Perfil não encontrado.');
            return ['type' => 'responsavel', 'profile' => $profile];
        }

        if ($userId) {
            $profile = $this->users->findActiveById($userId);
            if (!$profile) throw new RuntimeException('Perfil não encontrado.');
            return ['type' => 'usuario', 'profile' => $profile];
        }

        throw new RuntimeException('Perfil não encontrado.');
    }

    public function updatePhoto(?int $userId, ?int $guardianId, string $photoUrl): void
    {
        if ($guardianId) {
            $this->guardians->updatePhoto($guardianId, $photoUrl);
            $this->audit->record('alterar_foto_perfil', 'scp_responsaveis', $guardianId);
            return;
        }

        if ($userId) {
            $this->users->updatePhoto($userId, $photoUrl);
            $this->audit->record('alterar_foto_perfil', 'scp_usuarios', $userId);
            return;
        }

        throw new RuntimeException('Perfil não encontrado.');
    }
}
