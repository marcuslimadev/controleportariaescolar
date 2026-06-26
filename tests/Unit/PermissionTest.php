<?php
declare(strict_types=1);

use App\Support\Permission;

return static function (): void {
    if (!Permission::roleHas('admin', 'qualquer.coisa')) throw new RuntimeException('Admin deve ter wildcard.');
    if (!Permission::roleHas('portaria', 'access.write')) throw new RuntimeException('Portaria deve registrar acesso.');
    if (Permission::roleHas('portaria', 'post.manage')) throw new RuntimeException('Portaria não deve gerenciar posts.');
    if (!Permission::roleHas('secretaria', 'post.manage')) throw new RuntimeException('Secretaria deve gerenciar posts.');
    if (Permission::roleHas('responsavel', 'access.write')) throw new RuntimeException('Responsável não deve registrar acesso de portaria.');
};
