<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\QuickRegistrationRepository;
use App\Contracts\Services\AuditLogger;
use App\Support\PasswordService;
use RuntimeException;

final class QuickRegistrationService
{
    public function __construct(
        private QuickRegistrationRepository $registrations,
        private AuditLogger $audit,
    ) {}

    public function classes(): array
    {
        return $this->registrations->activeClasses();
    }

    public function create(array $input, ?string $studentPhoto, ?string $guardianPhoto): array
    {
        $data = $this->normalize($input, $studentPhoto, $guardianPhoto);
        if ($data['turma_id'] && !$this->registrations->classExists((int)$data['turma_id'])) {
            throw new RuntimeException('Selecione uma turma válida.');
        }

        $result = $this->registrations->create($data, bin2hex(random_bytes(32)), PasswordService::hash(bin2hex(random_bytes(8))));
        $this->audit->record('cadastro_rapido_aluno', 'scp_alunos', (int)$result['aluno_id'], ['responsavel_id' => (int)$result['responsavel_id']]);

        return $result;
    }

    private function normalize(array $input, ?string $studentPhoto, ?string $guardianPhoto): array
    {
        $name = trim((string)($input['nome'] ?? ''));
        $guardianName = trim((string)($input['responsavel_nome'] ?? ''));
        $guardianCpf = preg_replace('/\D/', '', (string)($input['responsavel_cpf'] ?? ''));
        $guardianPhone = preg_replace('/\D+/', '', (string)($input['responsavel_telefone'] ?? '')) ?: '';

        if (strlen($name) < 3) throw new RuntimeException('Informe o nome completo do aluno.');
        if (strlen($guardianName) < 3) throw new RuntimeException('Informe o nome completo do responsável.');
        if (strlen($guardianCpf) !== 11) throw new RuntimeException('Informe um CPF válido para o responsável (11 números).');
        if (strlen($guardianPhone) < 10) throw new RuntimeException('Informe um telefone com DDD para o responsável.');

        return [
            'nome' => $name,
            'turma_id' => !empty($input['turma_id']) ? (int)$input['turma_id'] : null,
            'cpf' => preg_replace('/\D/', '', (string)($input['cpf'] ?? '')) ?: null,
            'data_nascimento' => trim((string)($input['data_nascimento'] ?? '')) ?: null,
            'parentesco' => trim((string)($input['parentesco'] ?? '')) ?: 'Responsável',
            'foto' => $studentPhoto,
            'responsavel_nome' => $guardianName,
            'responsavel_cpf' => $guardianCpf,
            'responsavel_telefone' => $guardianPhone,
            'responsavel_foto' => $guardianPhoto,
        ];
    }
}
