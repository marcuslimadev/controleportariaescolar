<?php
require __DIR__ . '/../includes/bootstrap.php';
require_portal_access();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('feed.php');
verify_csrf();
$postId = (int)($_POST['post_id'] ?? 0);
[$visibilitySql, $visibilityParams] = post_visible_sql('p');
try {
    $service = new \App\Services\PostInteractionService(
        new \App\Infrastructure\Persistence\PdoPostInteractionRepository(db()),
        new \App\Infrastructure\Logging\DatabaseAuditLogger(),
    );
    $service->confirmScience($postId, ['responsavel_id' => $_SESSION['responsavel_id'] ?? null, 'user_id' => $_SESSION['user_id'] ?? null], $visibilitySql, $visibilityParams, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? '');
    flash('Ciência confirmada.');
} catch (Throwable $error) {
    flash($error->getMessage(), 'warning');
}
redirect('feed.php');
