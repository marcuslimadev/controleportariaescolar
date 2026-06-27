<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Contracts\Repositories\SchoolAdminRepository;
use PDO;

final class PdoSchoolAdminRepository implements SchoolAdminRepository
{
    public function __construct(private PDO $pdo) {}

    public function createClass(string $name, string $shift): int
    {
        $query = $this->pdo->prepare('INSERT INTO scp_turmas(nome,turno) VALUES(?,?)');
        $query->execute([$name, $shift]);
        return (int)$this->pdo->lastInsertId();
    }

    public function createStudent(array $data, string $qrToken): int
    {
        $query = $this->pdo->prepare('INSERT INTO scp_alunos(nome,cpf,data_nascimento,turma_id,foto,qr_token) VALUES(?,?,?,?,?,?)');
        $query->execute([
            $data['nome'],
            $data['cpf'],
            $data['data_nascimento'],
            $data['turma_id'],
            $data['foto'],
            $qrToken,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function createGuardian(array $data, string $passwordHash): int
    {
        $query = $this->pdo->prepare('INSERT INTO scp_responsaveis(nome,cpf,email,telefone,senha_hash) VALUES(?,?,?,?,?)');
        $query->execute([$data['nome'], $data['cpf'], $data['email'], $data['telefone'], $passwordHash]);
        return (int)$this->pdo->lastInsertId();
    }

    public function linkGuardian(array $data): void
    {
        $query = $this->pdo->prepare(
            'INSERT INTO scp_aluno_responsavel(aluno_id,responsavel_id,parentesco,autoriza_consulta,autoriza_retirada)
             VALUES(?,?,?,?,?)
             ON DUPLICATE KEY UPDATE parentesco=VALUES(parentesco),autoriza_consulta=VALUES(autoriza_consulta),autoriza_retirada=VALUES(autoriza_retirada)'
        );
        $query->execute([
            $data['aluno_id'],
            $data['responsavel_id'],
            $data['parentesco'],
            $data['autoriza_consulta'],
            $data['autoriza_retirada'],
        ]);
    }

    public function createUser(array $data, string $passwordHash): int
    {
        $query = $this->pdo->prepare('INSERT INTO scp_usuarios(nome,email,senha_hash,perfil) VALUES(?,?,?,?)');
        $query->execute([$data['nome'], $data['email'], $passwordHash, $data['perfil']]);
        return (int)$this->pdo->lastInsertId();
    }

    public function createTeacher(array $data): int
    {
        $query = $this->pdo->prepare('INSERT INTO scp_professores(usuario_id,nome,email,telefone,ativo) VALUES(?,?,?,?,1)');
        $query->execute([$data['usuario_id'], $data['nome'], $data['email'], $data['telefone']]);
        return (int)$this->pdo->lastInsertId();
    }

    public function linkTeacherClass(int $teacherId, int $classId): void
    {
        $query = $this->pdo->prepare('INSERT IGNORE INTO scp_professor_turma(professor_id,turma_id) VALUES(?,?)');
        $query->execute([$teacherId, $classId]);
    }

    public function toggleActive(string $table, int $id): void
    {
        $query = $this->pdo->prepare("UPDATE {$table} SET ativo=1-ativo WHERE id=?");
        $query->execute([$id]);
    }

    public function dashboardData(): array
    {
        return [
            'scp_turmas' => $this->pdo->query('SELECT * FROM scp_turmas ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC),
            'scp_alunos' => $this->pdo->query('SELECT a.*,t.nome turma FROM scp_alunos a LEFT JOIN scp_turmas t ON t.id=a.turma_id ORDER BY a.nome')->fetchAll(PDO::FETCH_ASSOC),
            'resp' => $this->pdo->query('SELECT * FROM scp_responsaveis ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC),
            'users' => $this->pdo->query('SELECT * FROM scp_usuarios ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC),
            'professores' => $this->pdo->query('SELECT p.*,u.email usuario_email FROM scp_professores p LEFT JOIN scp_usuarios u ON u.id=p.usuario_id ORDER BY p.nome')->fetchAll(PDO::FETCH_ASSOC),
            'profTurmas' => $this->pdo->query('SELECT pt.professor_id,GROUP_CONCAT(t.nome ORDER BY t.nome SEPARATOR ", ") turmas FROM scp_professor_turma pt JOIN scp_turmas t ON t.id=pt.turma_id GROUP BY pt.professor_id')->fetchAll(PDO::FETCH_KEY_PAIR),
        ];
    }
}
