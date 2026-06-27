<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\AbsenceRepository;
use App\Contracts\Services\AuditLogger;
use RuntimeException;

final class AbsenceService
{
    private const REASONS = ['Doença','Consulta médica','Viagem','Compromisso familiar','Outro'];
    private const STATUSES = ['enviado','visualizado','abonado','rejeitado'];
    private const REVIEW_STATUSES = ['visualizado','abonado','rejeitado'];

    public function __construct(
        private AbsenceRepository $absences,
        private AuditLogger $audit,
    ) {}

    public function childrenForGuardian(int $guardianId): array
    {
        return $this->absences->childrenForGuardian($guardianId);
    }

    public function createFromGuardian(int $guardianId, array $input, ?string $attachmentUrl): int
    {
        $children = $this->childrenForGuardian($guardianId);
        $studentId = (int)($input['aluno_id'] ?? 0);
        $child = null;
        foreach ($children as $candidate) {
            if ((int)$candidate['id'] === $studentId) {
                $child = $candidate;
                break;
            }
        }
        if (!$child) throw new RuntimeException('Aluno inválido.');

        $date = (string)($input['data_falta'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new RuntimeException('Informe a data da falta.');

        $reason = in_array($input['motivo'] ?? '', self::REASONS, true) ? (string)$input['motivo'] : 'Outro';
        $id = $this->absences->create([
            'aluno_id' => $studentId,
            'responsavel_id' => $guardianId,
            'turma_id' => $child['turma_id'] ?: null,
            'data_falta' => $date,
            'motivo' => $reason,
            'observacao' => trim((string)($input['observacao'] ?? '')) ?: null,
            'anexo_url' => $attachmentUrl,
        ]);
        $this->audit->record('enviar_aviso_falta', 'scp_avisos_falta', $id, ['aluno_id' => $studentId]);

        return $id;
    }

    public function updateStatus(int $id, string $status, int $reviewedBy): void
    {
        if (!in_array($status, self::REVIEW_STATUSES, true)) {
            throw new RuntimeException('Status inválido.');
        }
        $this->absences->updateStatus($id, $status, $reviewedBy);
        $this->audit->record('alterar_aviso_falta', 'scp_avisos_falta', $id, ['status' => $status]);
    }

    public function listForAdmin(?string $status): array
    {
        $status = in_array($status, self::STATUSES, true) ? $status : null;

        return $this->absences->listForAdmin($status);
    }

    public function listForTeacher(int $professorId): array
    {
        return $this->absences->listForTeacher($professorId);
    }

    public function listForGuardian(int $guardianId): array
    {
        return $this->absences->listForGuardian($guardianId);
    }
}
