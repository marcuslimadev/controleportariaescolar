<?php
require __DIR__.'/../../includes/bootstrap.php';
header('Content-Type: application/json');
require_permission('invite.manage');
$inviteService = new \App\Services\InviteService(
    new \App\Infrastructure\Persistence\PdoInviteRepository(db()),
    new \App\Infrastructure\Persistence\PdoGuardianRepository(db()),
    new \App\Infrastructure\Persistence\PdoStudentRepository(db()),
    new \App\Infrastructure\Logging\DatabaseAuditLogger(),
    db(),
);
echo json_encode($inviteService->pendingSummary(), JSON_UNESCAPED_UNICODE);
