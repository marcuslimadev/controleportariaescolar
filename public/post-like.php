<?php
require __DIR__ . '/../includes/bootstrap.php';
require_portal_access();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('feed.php');
verify_csrf();
$postId = (int)($_POST['post_id'] ?? 0);
[$visibilitySql, $visibilityParams] = post_visible_sql('p');
try {
    $service = \App\Support\ServiceFactory::postInteractions();
    $service->toggleLike($postId, ['responsavel_id' => $_SESSION['responsavel_id'] ?? null, 'user_id' => $_SESSION['user_id'] ?? null], $visibilitySql, $visibilityParams);
} catch (Throwable $error) {
    flash($error->getMessage(), 'warning');
}
redirect('feed.php');
