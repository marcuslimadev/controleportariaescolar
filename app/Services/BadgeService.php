<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\BadgeRepository;
use App\Contracts\Services\AuditLogger;
use RuntimeException;

final class BadgeService
{
    public function __construct(
        private BadgeRepository $badges,
        private AuditLogger $audit,
    ) {}

    public function publicGuardianBadge(?string $qrToken, ?string $inviteToken, int $guardianIdParam, ?int $sessionGuardianId): ?array
    {
        $guardian = null;
        if ($qrToken) {
            $guardian = $this->badges->findGuardianByQrToken($qrToken);
        }
        if (!$guardian && $inviteToken && $guardianIdParam > 0) {
            $guardian = $this->badges->findGuardianByApprovedInvite($inviteToken, $guardianIdParam);
        }
        if (!$guardian && $sessionGuardianId) {
            $guardian = $this->badges->findActiveGuardianById($sessionGuardianId);
        }
        if (!$guardian) {
            return null;
        }

        $guardian = $this->ensureGuardianToken($guardian);
        return [
            'guardian' => $guardian,
            'children' => $this->badges->withdrawalChildren((int)$guardian['id']),
        ];
    }

    public function adminGuardianBadge(int $guardianId, bool $emit, int $issuedBy): array
    {
        $guardian = $this->badges->findGuardianById($guardianId);
        if (!$guardian) {
            throw new RuntimeException('Responsável não encontrado.');
        }
        $guardian = $this->ensureGuardianToken($guardian);
        if ($emit) {
            $this->badges->recordGuardianIssue($guardianId, $issuedBy, (string)$guardian['qr_token']);
            $this->audit->record('emitir_cracha_responsavel', 'scp_responsaveis', $guardianId);
        }

        return [
            'guardian' => $guardian,
            'children' => $this->badges->withdrawalChildren($guardianId),
        ];
    }

    public function securityBadge(int $studentId): array
    {
        $student = $this->badges->findActiveStudentSecurityBadge($studentId);
        if (!$student) {
            throw new RuntimeException('Aluno não encontrado.');
        }

        return $student;
    }

    private function ensureGuardianToken(array $guardian): array
    {
        if (empty($guardian['qr_token'])) {
            $guardian['qr_token'] = bin2hex(random_bytes(32));
            $this->badges->updateGuardianQrToken((int)$guardian['id'], (string)$guardian['qr_token']);
        }

        return $guardian;
    }
}
