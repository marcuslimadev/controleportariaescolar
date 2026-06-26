<?php
declare(strict_types=1);

use App\Support\PasswordService;

return static function (): void {
    $hash = PasswordService::hash('Senha@123456');
    if (!PasswordService::verify('Senha@123456', $hash)) throw new RuntimeException('Senha correta não validou.');
    if (PasswordService::verify('senha-errada', $hash)) throw new RuntimeException('Senha errada validou.');
    if ($hash === 'Senha@123456') throw new RuntimeException('Senha não foi hasheada.');
};
