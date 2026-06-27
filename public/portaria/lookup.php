<?php
require __DIR__.'/../../includes/bootstrap.php';
header('Content-Type: application/json');
require_permission('access.read');
if($_SERVER['REQUEST_METHOD']!=='POST')exit('{}');
verify_csrf();
$token=extract_qr_token((string)($_POST['token']??''));

$service = \App\Support\ServiceFactory::accessLookup();
echo json_encode($service->lookupGuardianBadge($token));
