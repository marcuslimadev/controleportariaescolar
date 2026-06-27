<?php
declare(strict_types=1);

use App\Contracts\Repositories\QuickRegistrationRepository;
use App\Services\QuickRegistrationService;

final class InMemoryQuickRegistrationRepository implements QuickRegistrationRepository
{
    public array $created = [];
    public bool $classExists = true;

    public function activeClasses(): array
    {
        return [['id' => 1, 'nome' => '1A', 'turno' => 'manha']];
    }

    public function classExists(int $classId): bool
    {
        return $this->classExists && $classId === 1;
    }

    public function create(array $data, string $studentQrToken, string $guardianPasswordHash): array
    {
        $this->created[] = compact('data', 'studentQrToken', 'guardianPasswordHash');
        return ['aluno_id' => 10, 'responsavel_id' => 7];
    }
}

final class QuickRegistrationSpyAuditLogger implements \App\Contracts\Services\AuditLogger
{
    public array $records = [];

    public function record(string $action, ?string $entity = null, ?int $entityId = null, array $details = []): void
    {
        $this->records[] = compact('action', 'entity', 'entityId', 'details');
    }
}

return static function (): void {
    $repo = new InMemoryQuickRegistrationRepository();
    $audit = new QuickRegistrationSpyAuditLogger();
    $service = new QuickRegistrationService($repo, $audit);

    $result = $service->create([
        'nome' => 'Aluno Completo',
        'turma_id' => '1',
        'responsavel_nome' => 'Responsável Completo',
        'responsavel_cpf' => '111.444.777-35',
        'responsavel_telefone' => '(91) 96321-42134',
        'parentesco' => 'Mãe',
    ], 'aluno.jpg', 'resp.jpg');

    if (($result['aluno_id'] ?? null) !== 10) throw new RuntimeException('Cadastro rápido não retornou aluno.');
    if (($repo->created[0]['data']['responsavel_cpf'] ?? null) !== '11144477735') throw new RuntimeException('CPF do responsável não foi normalizado.');
    if (($repo->created[0]['data']['responsavel_telefone'] ?? null) !== '919632142134') throw new RuntimeException('Telefone do responsável não foi normalizado.');
    if (($audit->records[0]['action'] ?? null) !== 'cadastro_rapido_aluno') throw new RuntimeException('Cadastro rápido não auditou.');

    $repo->classExists = false;
    $blocked = false;
    try {
        $service->create([
            'nome' => 'Aluno Completo',
            'turma_id' => '2',
            'responsavel_nome' => 'Responsável Completo',
            'responsavel_cpf' => '11144477735',
            'responsavel_telefone' => '919632142134',
        ], null, null);
    } catch (RuntimeException) {
        $blocked = true;
    }
    if (!$blocked) throw new RuntimeException('Turma inválida não foi bloqueada.');
};
