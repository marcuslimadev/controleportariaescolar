<?php
require __DIR__.'/../../includes/bootstrap.php';
header('Content-Type: application/json');
require_permission('invite.manage');
$inviteService = \App\Support\ServiceFactory::invites();
echo json_encode($inviteService->pendingSummary(), JSON_UNESCAPED_UNICODE);
