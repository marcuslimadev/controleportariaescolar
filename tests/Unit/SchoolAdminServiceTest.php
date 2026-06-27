<?php
declare(strict_types=1);

use App\Contracts\Repositories\SchoolAdminRepository;
use App\Services\SchoolAdminService;

final class InMemorySchoolAdminRepository implements SchoolAdminRepository
{
    public array $actions = [];

    public function createClass(string $name, string $shift): int { $this->actions[] = ['class', $name, $shift]; return 1; }
    public function createStudent(array $data, string $qrToken): int { $this->actions[] = ['student', $data, $qrToken]; return 2; }
    public function createGuardian(array $data, string $passwordHash): int { $this->actions[] = ['guardian', $data, $passwordHash]; return 3; }
    public function linkGuardian(array $data): void { $this->actions[] = ['link_guardian', $data]; }
    public function createUser(array $data, string $passwordHash): int { $this->actions[] = ['user', $data, $passwordHash]; return 4; }
    public function createTeacher(array $data): int { $this->actions[] = ['teacher', $data]; return 5; }
    public function linkTeacherClass(int $teacherId, int $classId): void { $this->actions[] = ['teacher_class', $teacherId, $classId]; }
    public function toggleActive(string $table, int $id): void { $this->actions[] = ['toggle', $table, $id]; }
    public function saveSetting(string $key, string $value): void { $this->actions[] = ['setting', $key, $value]; }
    public function dashboardData(): array { return ['scp_turmas' => []]; }
}

return static function (): void {
    $repo = new InMemorySchoolAdminRepository();
    $audit = new AbsenceSpyAuditLogger();
    $service = new SchoolAdminService($repo, $audit);

    $service->handleAction(['action' => 'turma', 'nome' => '1A', 'turno' => 'tarde'], 'secretaria');
    if (($repo->actions[0][0] ?? null) !== 'class') throw new RuntimeException('Turma não foi criada via service.');

    $service->handleAction(['action' => 'aluno', 'nome' => 'Aluno', 'cpf' => '123', 'data_nascimento' => '', 'turma_id' => '', 'foto' => ''], 'secretaria');
    if (($repo->actions[1][0] ?? null) !== 'student') throw new RuntimeException('Aluno não foi criado via service.');

    $blocked = false;
    try {
        $service->handleAction(['action' => 'usuario', 'nome' => 'Porteiro', 'email' => 'p@example.com', 'senha' => 'Senha@12345'], 'secretaria');
    } catch (RuntimeException) {
        $blocked = true;
    }
    if (!$blocked) throw new RuntimeException('Usuário não-admin não foi bloqueado.');

    $service->handleAction(['action' => 'toggle', 'table' => 'scp_alunos', 'id' => 2], 'secretaria');
    if (($repo->actions[2][0] ?? null) !== 'toggle') throw new RuntimeException('Toggle não passou pelo service.');
};
