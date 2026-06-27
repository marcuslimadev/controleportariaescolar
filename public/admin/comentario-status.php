<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('post.manage');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/comentarios.php');
verify_csrf();
try {
    $service = \App\Support\ServiceFactory::postInteractions();
    $service->moderateComment((int)($_POST['id'] ?? 0), (string)($_POST['status'] ?? ''), (int)$_SESSION['user_id']);
    flash('Comentário moderado.');
} catch (Throwable $error) {
    flash($error->getMessage(), 'warning');
}
redirect('admin/comentarios.php');
