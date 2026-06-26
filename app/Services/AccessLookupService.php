<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\GuardianRepository;
use App\Contracts\Repositories\StudentRepository;

final class AccessLookupService
{
    public function __construct(
        private GuardianRepository $guardians,
        private StudentRepository $students,
    ) {}

    public function lookupGuardianBadge(string $token): array
    {
        $guardian = $this->guardians->findActiveByQrToken($token);
        if (!$guardian) {
            if ($this->students->activeExistsByQrToken($token)) {
                return [
                    'ok' => false,
                    'message' => 'Este é o crachá de segurança da criança, não o crachá de retirada. Peça para o responsável apresentar o crachá dele.',
                ];
            }

            return ['ok' => false];
        }

        $children = array_map(static function (array $student): array {
            $inside = ($student['ultimo'] ?? null) === 'entrada';

            return [
                'id' => (int)$student['id'],
                'nome' => $student['nome'],
                'foto' => $student['foto'],
                'turma' => $student['turma'],
                'dentro' => $inside,
                'sugerida' => $inside ? 'saida' : 'entrada',
            ];
        }, $this->guardians->authorizedChildrenForWithdrawal((int)$guardian['id']));

        if (!$children) {
            return ['ok' => false, 'message' => 'Nenhuma criança autorizada para retirada está vinculada a este responsável.'];
        }

        return [
            'ok' => true,
            'responsavel' => [
                'id' => (int)$guardian['id'],
                'nome' => $guardian['nome'],
                'foto' => $guardian['foto'],
                'token' => $guardian['qr_token'],
            ],
            'children' => $children,
        ];
    }
}
