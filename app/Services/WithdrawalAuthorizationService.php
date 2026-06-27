<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\WithdrawalAuthorizationRepository;
use App\Contracts\Services\AuditLogger;
use RuntimeException;

final class WithdrawalAuthorizationService
{
    public function __construct(
        private WithdrawalAuthorizationRepository $authorizations,
        private AuditLogger $audit,
    ) {}

    public function guardianDashboard(int $guardianId): array
    {
        return [
            'children' => $this->authorizations->childrenForGuardian($guardianId),
            'rows' => $this->authorizations->listForGuardian($guardianId),
        ];
    }

    public function create(int $guardianId, array $input): int
    {
        $studentId = (int)($input['aluno_id'] ?? 0);
        if (!$this->authorizations->guardianCanAuthorize($guardianId, $studentId)) {
            throw new RuntimeException('Selecione um aluno vinculado à sua conta.');
        }

        $name = trim((string)($input['nome_autorizado'] ?? ''));
        if ($name === '' || strlen($name) > 180) {
            throw new RuntimeException('Informe o nome da pessoa autorizada.');
        }

        $validUntil = (string)($input['valido_ate'] ?? '');
        $today = date('Y-m-d');
        $max = date('Y-m-d', strtotime('+30 days'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $validUntil) || $validUntil < $today || $validUntil > $max) {
            throw new RuntimeException('A validade deve ser de hoje até no máximo 30 dias.');
        }

        $id = $this->authorizations->create([
            'aluno_id' => $studentId,
            'responsavel_id' => $guardianId,
            'nome_autorizado' => $name,
            'documento' => trim((string)($input['documento'] ?? '')) ?: null,
            'telefone' => trim((string)($input['telefone'] ?? '')) ?: null,
            'valido_ate' => $validUntil,
            'observacao' => trim((string)($input['observacao'] ?? '')) ?: null,
        ]);
        $this->audit->record('criar_autorizacao_retirada', 'scp_autorizacoes_retirada', $id, ['aluno_id' => $studentId]);

        return $id;
    }

    public function gateList(): array
    {
        return $this->authorizations->activeForGate();
    }

    public function markUsed(int $id, int $operatorId): void
    {
        $this->authorizations->updateStatus($id, 'usada', $operatorId);
        $this->audit->record('usar_autorizacao_retirada', 'scp_autorizacoes_retirada', $id);
    }

    public function cancel(int $id, int $guardianId): void
    {
        $this->authorizations->cancelForGuardian($id, $guardianId);
        $this->audit->record('cancelar_autorizacao_retirada', 'scp_autorizacoes_retirada', $id, ['responsavel_id' => $guardianId]);
    }
}
