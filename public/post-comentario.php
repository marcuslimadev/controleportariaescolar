<?php
require __DIR__ . '/../includes/bootstrap.php';
require_portal_access();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('feed.php');
verify_csrf();
$postId = (int)($_POST['post_id'] ?? 0);
$comment = (string)($_POST['comentario'] ?? '');
[$visibilitySql, $visibilityParams] = post_visible_sql('p');
try {
    $service = \App\Support\ServiceFactory::postInteractions();
    $service->addComment($postId, $comment, ['responsavel_id' => $_SESSION['responsavel_id'] ?? null, 'user_id' => $_SESSION['user_id'] ?? null], $visibilitySql, $visibilityParams);
    flash('Comentário enviado para moderação.');
} catch (Throwable $error) {
    flash($error->getMessage(), 'warning');
}
redirect('feed.php');
