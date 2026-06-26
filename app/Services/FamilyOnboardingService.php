<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\InviteRepository;
use App\Contracts\Services\AuditLogger;
use App\Support\PasswordService;
use RuntimeException;

final class FamilyOnboardingService
{
    public function __construct(
        private InviteRepository $invites,
        private AuditLogger $audit,
    ) {}

    public function getInvite(string $token): array
    {
        $invite = $this->invites->findByPublicToken($token);
        if (!$invite) {
            return ['invite' => null, 'invalid' => true];
        }

        if (($invite['status'] ?? '') === 'aguardando' && strtotime((string)$invite['expira_em']) < time()) {
            $this->invites->expire((int)$invite['id']);
            $invite['status'] = 'expirado';
            return ['invite' => $invite, 'invalid' => true];
        }

        return ['invite' => $invite, 'invalid' => false];
    }

    public function fillInvite(int $id, array $input, string $guardianPhoto, string $studentPhoto): void
    {
        $guardianName = trim((string)($input['responsavel_nome'] ?? ''));
        $cpf = preg_replace('/\D+/', '', (string)($input['cpf'] ?? '')) ?: '';
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $studentName = trim((string)($input['aluno_nome'] ?? ''));
        $birthDate = trim((string)($input['data_nascimento'] ?? ''));
        $password = (string)($input['senha'] ?? '');
        $confirmPassword = (string)($input['confirmar_senha'] ?? '');

        if (strlen($guardianName) < 3 || strlen($studentName) < 3) throw new RuntimeException('Informe os nomes completos.');
        if (strlen($cpf) !== 11) throw new RuntimeException('Informe um CPF com 11 números.');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Informe um e-mail válido.');
        if ($birthDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate)) throw new RuntimeException('Informe uma data de nascimento válida.');
        if (strlen($password) < 8) throw new RuntimeException('A senha precisa ter pelo menos 8 caracteres.');
        if (!hash_equals($password, $confirmPassword)) throw new RuntimeException('As senhas não coincidem.');

        $this->invites->fillByFamily($id, [
            'responsavel_nome' => $guardianName,
            'responsavel_cpf' => $cpf,
            'responsavel_email' => $email,
            'responsavel_foto' => $guardianPhoto,
            'aluno_nome' => $studentName,
            'aluno_data_nascimento' => $birthDate,
            'aluno_foto' => $studentPhoto,
            'senha_hash' => PasswordService::hash($password),
        ]);

        $this->audit->record('preencher_convite_cadastro', 'scp_convites_cadastro', $id);
    }
}
