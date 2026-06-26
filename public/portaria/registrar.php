<?php
require __DIR__.'/../../includes/bootstrap.php';
header('Content-Type: application/json');
require_role(['admin','secretaria','portaria']);
verify_csrf();

$token=extract_qr_token((string)($_POST['token']??''));
$items=json_decode((string)($_POST['items']??'[]'),true);
if(!is_array($items))$items=[];

try {
    $service = new \App\Services\AccessService(
        new \App\Infrastructure\Persistence\PdoGuardianRepository(db()),
        new \App\Infrastructure\Persistence\PdoAccessLogRepository(db()),
        new \App\Infrastructure\Logging\DatabaseAuditLogger(),
    );
    $result = $service->registerGuardianAccess(
        $token,
        $items,
        (int)$_SESSION['user_id'],
        substr($_SERVER['HTTP_USER_AGENT']??'',0,100),
        $_SERVER['REMOTE_ADDR']??null
    );
    echo json_encode(['message'=>$result['message']]);
} catch (RuntimeException $error) {
    $code = $error->getMessage() === 'Responsável não encontrado.' ? 404 : 422;
    http_response_code($code);
    echo json_encode(['message'=>$error->getMessage()]);
}
