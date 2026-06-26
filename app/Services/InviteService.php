<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\GuardianRepository;
use App\Contracts\Repositories\InviteRepository;
use App\Contracts\Repositories\StudentRepository;
use App\Contracts\Services\AuditLogger;
use RuntimeException;

final class InviteService
{
    public function __construct(
        private InviteRepository $invites,
        private GuardianRepository $guardians,
        private StudentRepository $students,
        private AuditLogger $audit,
        private object $pdo,
    ) {}

    public function createInvite(string $phone, int $createdBy): array
    {
        $phone = preg_replace('/\D+/', '', $phone) ?: '';
        if (strlen($phone) < 10 || strlen($phone) > 13) {
            throw new RuntimeException('Informe um telefone com DDD.');
        }

        $token = bin2hex(random_bytes(32));
        $id = $this->invites->create($phone, hash('sha256', $token), $createdBy);
        $this->audit->record('criar_convite_cadastro', 'scp_convites_cadastro', $id, ['telefone_final' => substr($phone, -4)]);

        return ['id' => $id, 'token' => $token, 'telefone' => $phone];
    }

    public function approveInvite(int $id, int $approvedBy): array
    {
        $this->pdo->beginTransaction();
        try {
            $invite = $this->invites->findReadyForApproval($id);
            if (!$invite) {
                throw new RuntimeException('Este cadastro não está pronto para aprovação.');
            }

            $guardianId = $this->guardians->findIdByCpf((string)$invite['responsavel_cpf']);
            if ($guardianId) {
                $this->guardians->updateFromInvite($guardianId, $invite);
            } else {
                $guardianId = $this->guardians->createFromInvite($invite);
            }

            $studentId = $this->students->createFromInvite($invite, bin2hex(random_bytes(32)));
            $this->students->linkGuardian($studentId, $guardianId);
            $this->invites->markApproved($id, $approvedBy, $guardianId, $studentId);
            $this->pdo->commit();

            $this->audit->record('aprovar_cadastro_responsavel', 'scp_convites_cadastro', $id, ['aluno_id' => $studentId]);

            return ['responsavel_id' => $guardianId, 'aluno_id' => $studentId];
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function refreshPendingList(int $limit = 30): array
    {
        $this->invites->expireOld();

        return $this->invites->pendingList($limit);
    }
}
