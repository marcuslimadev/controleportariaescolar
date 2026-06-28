<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\AccessLogRepository;
use App\Contracts\Repositories\GuardianRepository;
use App\Contracts\Services\AuditLogger;
use RuntimeException;

final class AccessService
{
    public function __construct(
        private GuardianRepository $guardians,
        private AccessLogRepository $accessLogs,
        private AuditLogger $audit,
    ) {}

    public function registerGuardianAccess(
        string $token,
        array $items,
        int $operatorId,
        string $origin,
        ?string $ip
    ): array {
        if ($items === []) {
            throw new RuntimeException('Nenhuma criança selecionada.');
        }

        $guardianId = $this->guardians->findActiveIdByQrToken($token);
        if (!$guardianId) {
            throw new RuntimeException('Responsável não encontrado.');
        }

        $registered = [];
        foreach ($items as $item) {
            $studentId = (int)($item['aluno_id'] ?? 0);
            $type = (string)($item['tipo'] ?? '');
            $manual = !empty($item['manual']);
            $note = trim((string)($item['observacao'] ?? ''));
            $clientUid = $this->clientUid($item['client_uid'] ?? null);

            if (!in_array($type, ['entrada', 'saida'], true)) {
                continue;
            }
            if ($clientUid !== null && $this->accessLogs->clientUidExists($clientUid)) {
                $registered[] = $type;
                continue;
            }
            if ($manual && strlen($note) < 5) {
                continue;
            }
            if (!$this->guardians->canWithdrawStudent($guardianId, $studentId)) {
                continue;
            }

            $this->accessLogs->record($studentId, $guardianId, $type, $operatorId, $origin, $note !== '' ? $note : null, $manual, $ip, $clientUid);
            $this->audit->record($manual ? 'correcao_manual' : 'registrar_acesso', 'scp_alunos', $studentId, [
                'tipo' => $type,
                'responsavel_id' => $guardianId,
            ]);
            $registered[] = $type;
        }

        if ($registered === []) {
            throw new RuntimeException('Não foi possível registrar as crianças selecionadas.');
        }

        return [
            'registered' => $registered,
            'message' => $this->messageFor($registered),
        ];
    }

    private function messageFor(array $registered): string
    {
        $entradas = count(array_filter($registered, static fn(string $type): bool => $type === 'entrada'));
        $saidas = count($registered) - $entradas;
        $parts = [];
        if ($entradas > 0) $parts[] = $entradas . ' entrada' . ($entradas > 1 ? 's' : '');
        if ($saidas > 0) $parts[] = $saidas . ' saída' . ($saidas > 1 ? 's' : '');

        return implode(' e ', $parts) . ' registrada' . (count($registered) > 1 ? 's' : '') . ' com sucesso.';
    }

    private function clientUid(mixed $value): ?string
    {
        $uid = trim((string)$value);
        if ($uid === '') return null;
        return preg_match('/^[A-Za-z0-9._:-]{8,80}$/', $uid) ? $uid : null;
    }
}
