<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\EmergencyBadgeRepository;
use App\Contracts\Services\AuditLogger;
use RuntimeException;

final class EmergencyBadgeService
{
    public function __construct(
        private EmergencyBadgeRepository $badges,
        private AuditLogger $audit,
    ) {}

    public function findPublicBadge(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        return $this->badges->findActiveStudentByToken($token);
    }

    public function redirectForLoggedActor(array $student, array $session, string $token): ?string
    {
        if (!empty($session['user_id']) && in_array($session['role'] ?? '', ['admin','secretaria','portaria'], true)) {
            return 'portaria/index.php?token=' . rawurlencode($token);
        }
        if (!empty($session['responsavel_id']) && $this->badges->guardianCanConsult((int)$student['id'], (int)$session['responsavel_id'])) {
            return 'cracha.php?token=' . rawurlencode($token);
        }

        return null;
    }

    public function createPublicAlert(array $student, string $token, array $input, string $ip, string $userAgent): int
    {
        $id = $this->badges->createAlert([
            'aluno_id' => (int)$student['id'],
            'qr_token' => $token,
            'nome_informante' => $this->nullableText($input['nome'] ?? '', 150),
            'telefone_informante' => $this->nullableText($input['telefone'] ?? '', 30),
            'mensagem' => $this->nullableText($input['mensagem'] ?? '', 500),
            'latitude' => $this->nullableCoordinate($input['latitude'] ?? ''),
            'longitude' => $this->nullableCoordinate($input['longitude'] ?? ''),
            'ip' => $ip ?: null,
            'user_agent' => substr($userAgent, 0, 500),
        ]);
        $this->audit->record('alerta_cracha_publico', 'scp_alertas_cracha', $id, ['aluno_id' => (int)$student['id']]);

        return $id;
    }

    private function nullableText(mixed $value, int $max): ?string
    {
        $text = trim((string)$value);
        if ($text === '') return null;
        return substr($text, 0, $max);
    }

    private function nullableCoordinate(mixed $value): ?string
    {
        $text = trim((string)$value);
        if ($text === '') return null;
        if (!is_numeric($text)) throw new RuntimeException('Localização inválida.');
        return $text;
    }
}
