<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\SchoolAdminRepository;
use App\Contracts\Services\AuditLogger;
use App\Support\PasswordService;
use RuntimeException;

final class SchoolAdminService
{
    private const PROFILES = ['admin','secretaria','portaria','professor'];
    private const SHIFTS = ['manha','tarde','integral','noite'];

    public function __construct(
        private SchoolAdminRepository $school,
        private AuditLogger $audit,
    ) {}

    public function handleAction(array $input, string $role): void
    {
        $action = (string)($input['action'] ?? '');
        match ($action) {
            'turma' => $this->createClass($input),
            'aluno' => $this->createStudent($input),
            'responsavel' => $this->createGuardian($input),
            'vinculo' => $this->linkGuardian($input),
            'usuario' => $this->adminOnly($role, fn() => $this->createUser($input)),
            'professor' => $this->adminOnly($role, fn() => $this->createTeacher($input)),
            'professor_turma' => $this->adminOnly($role, fn() => $this->linkTeacherClass($input)),
            'tema' => $this->adminOnly($role, fn() => $this->saveTheme($input)),
            'toggle' => $this->toggle($input, $role),
            default => throw new RuntimeException('Ação inválida.'),
        };
    }

    public function dashboardData(): array
    {
        return $this->school->dashboardData();
    }

    private function createClass(array $input): void
    {
        $name = trim((string)($input['nome'] ?? ''));
        if ($name === '') throw new RuntimeException('Informe o nome da turma.');
        $shift = in_array($input['turno'] ?? '', self::SHIFTS, true) ? (string)$input['turno'] : 'manha';
        $id = $this->school->createClass($name, $shift);
        $this->audit->record('criar_turma', 'scp_turmas', $id);
    }

    private function createStudent(array $input): void
    {
        $name = trim((string)($input['nome'] ?? ''));
        if ($name === '') throw new RuntimeException('Informe o nome do aluno.');
        $id = $this->school->createStudent([
            'nome' => $name,
            'cpf' => preg_replace('/\D/', '', (string)($input['cpf'] ?? '')) ?: null,
            'data_nascimento' => $input['data_nascimento'] ?: null,
            'turma_id' => !empty($input['turma_id']) ? (int)$input['turma_id'] : null,
            'foto' => filter_var($input['foto'] ?? '', FILTER_VALIDATE_URL) ? (string)$input['foto'] : null,
        ], bin2hex(random_bytes(32)));
        $this->audit->record('criar_aluno', 'scp_alunos', $id);
    }

    private function createGuardian(array $input): void
    {
        $name = trim((string)($input['nome'] ?? ''));
        $cpf = preg_replace('/\D/', '', (string)($input['cpf'] ?? ''));
        $password = (string)($input['senha'] ?? '');
        if ($name === '' || $cpf === '' || strlen($password) < 8) throw new RuntimeException('Dados do responsável incompletos.');
        $id = $this->school->createGuardian([
            'nome' => $name,
            'cpf' => $cpf,
            'email' => strtolower(trim((string)($input['email'] ?? ''))),
            'telefone' => trim((string)($input['telefone'] ?? '')),
        ], PasswordService::hash($password));
        $this->audit->record('criar_responsavel', 'scp_responsaveis', $id);
    }

    private function linkGuardian(array $input): void
    {
        $studentId = (int)($input['aluno_id'] ?? 0);
        $guardianId = (int)($input['responsavel_id'] ?? 0);
        if ($studentId <= 0 || $guardianId <= 0) throw new RuntimeException('Selecione aluno e responsável.');
        $this->school->linkGuardian([
            'aluno_id' => $studentId,
            'responsavel_id' => $guardianId,
            'parentesco' => trim((string)($input['parentesco'] ?? '')),
            'autoriza_consulta' => isset($input['consulta']) ? 1 : 0,
            'autoriza_retirada' => isset($input['retirada']) ? 1 : 0,
        ]);
        $this->audit->record('vincular_responsavel', 'scp_alunos', $studentId);
    }

    private function createUser(array $input): void
    {
        $profile = in_array($input['perfil'] ?? '', self::PROFILES, true) ? (string)$input['perfil'] : 'portaria';
        $password = (string)($input['senha'] ?? '');
        if (strlen($password) < 8) throw new RuntimeException('Senha deve ter pelo menos 8 caracteres.');
        $id = $this->school->createUser([
            'nome' => trim((string)($input['nome'] ?? '')),
            'email' => strtolower(trim((string)($input['email'] ?? ''))),
            'perfil' => $profile,
        ], PasswordService::hash($password));
        $this->audit->record('criar_usuario', 'scp_usuarios', $id, ['perfil' => $profile]);
    }

    private function createTeacher(array $input): void
    {
        $id = $this->school->createTeacher([
            'usuario_id' => !empty($input['usuario_id']) ? (int)$input['usuario_id'] : null,
            'nome' => trim((string)($input['nome'] ?? '')),
            'email' => strtolower(trim((string)($input['email'] ?? ''))),
            'telefone' => trim((string)($input['telefone'] ?? '')),
        ]);
        $this->audit->record('criar_professor', 'scp_professores', $id);
    }

    private function linkTeacherClass(array $input): void
    {
        $teacherId = (int)($input['professor_id'] ?? 0);
        $classId = (int)($input['turma_id'] ?? 0);
        if ($teacherId <= 0 || $classId <= 0) throw new RuntimeException('Selecione professor e turma.');
        $this->school->linkTeacherClass($teacherId, $classId);
        $this->audit->record('vincular_professor_turma', 'scp_professores', $teacherId, ['turma_id' => $classId]);
    }

    private function toggle(array $input, string $role): void
    {
        $allowed = $role === 'admin'
            ? ['scp_alunos','scp_responsaveis','scp_turmas','scp_usuarios','scp_professores']
            : ['scp_alunos','scp_responsaveis','scp_turmas'];
        $table = in_array($input['table'] ?? '', $allowed, true) ? (string)$input['table'] : '';
        $id = (int)($input['id'] ?? 0);
        if ($table === '' || $id <= 0) throw new RuntimeException('Inválido.');
        $this->school->toggleActive($table, $id);
        $this->audit->record('alterar_status', $table, $id);
    }

    private function saveTheme(array $input): void
    {
        $theme = in_array($input['tema'] ?? '', ['classico','azul_branco','preto_branco'], true) ? (string)$input['tema'] : 'classico';
        $this->school->saveSetting('tema', $theme);
        $this->audit->record('alterar_tema', 'scp_configuracoes', null, ['tema' => $theme]);
    }

    private function adminOnly(string $role, callable $action): void
    {
        if ($role !== 'admin') throw new RuntimeException('Sem permissão.');
        $action();
    }
}
