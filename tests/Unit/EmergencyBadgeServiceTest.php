<?php
declare(strict_types=1);

use App\Contracts\Repositories\EmergencyBadgeRepository;
use App\Services\EmergencyBadgeService;

final class InMemoryEmergencyBadgeRepository implements EmergencyBadgeRepository
{
    public array $alerts = [];

    public function findActiveStudentByToken(string $token): ?array
    {
        return $token === 'TOKEN' ? ['id' => 10, 'qr_token' => $token] : null;
    }

    public function guardianCanConsult(int $studentId, int $guardianId): bool
    {
        return $studentId === 10 && $guardianId === 7;
    }

    public function createAlert(array $data): int
    {
        $this->alerts[] = $data;
        return 99;
    }
}

return static function (): void {
    $repo = new InMemoryEmergencyBadgeRepository();
    $audit = new AbsenceSpyAuditLogger();
    $service = new EmergencyBadgeService($repo, $audit);

    $student = $service->findPublicBadge('TOKEN');
    if (($student['id'] ?? null) !== 10) throw new RuntimeException('Crachá público não localizou aluno ativo.');

    $portariaRedirect = $service->redirectForLoggedActor($student, ['user_id' => 1, 'role' => 'portaria'], 'TOKEN');
    if ($portariaRedirect !== 'portaria/index.php?token=TOKEN') throw new RuntimeException('Portaria não foi redirecionada.');

    $guardianRedirect = $service->redirectForLoggedActor($student, ['responsavel_id' => 7], 'TOKEN');
    if ($guardianRedirect !== 'cracha.php?token=TOKEN') throw new RuntimeException('Responsável autorizado não foi redirecionado.');

    $id = $service->createPublicAlert($student, 'TOKEN', ['nome' => '  Pessoa  ', 'latitude' => '-1.2', 'longitude' => '-48.3'], '127.0.0.1', 'UA');
    if ($id !== 99) throw new RuntimeException('Alerta público não retornou id.');
    if (($repo->alerts[0]['nome_informante'] ?? null) !== 'Pessoa') throw new RuntimeException('Informante não foi normalizado.');
    if (($audit->records[0]['action'] ?? null) !== 'alerta_cracha_publico') throw new RuntimeException('Alerta público não auditou.');
};
