<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('post.manage');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/posts.php');
verify_csrf();

try {
    $id = (int)($_POST['id'] ?? 0);
    $service = new \App\Services\PostService(
        new \App\Infrastructure\Persistence\PdoPostRepository(db()),
        new \App\Infrastructure\Logging\DatabaseAuditLogger(),
    );
    $service->deletePost($id, (int)$_SESSION['user_id'], (string)($_SESSION['role'] ?? ''));
    flash('Publicação excluída.');
} catch (Throwable $e) {
    flash('Não foi possível excluir: '.$e->getMessage(), 'danger');
}

redirect('admin/posts.php');
