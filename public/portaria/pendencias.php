<?php
require __DIR__.'/../../includes/bootstrap.php';
header('Content-Type: application/json');
require_role(['admin','secretaria','portaria']);
$count=(int)db()->query("SELECT COUNT(*) FROM scp_convites_cadastro WHERE status='preenchido'")->fetchColumn();
$latest=db()->query("SELECT MAX(preenchido_em) FROM scp_convites_cadastro WHERE status='preenchido'")->fetchColumn();
echo json_encode(['count'=>$count,'latest'=>$latest]);
