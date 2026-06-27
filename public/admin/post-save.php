<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('post.manage');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/posts.php');
verify_csrf();

try {
    $imagemUrl = null;
    if (!empty($_FILES['imagem'])) $imagemUrl = save_portal_upload($_FILES['imagem'], 'posts', 'image');
    $service = \App\Support\ServiceFactory::posts();
    $service->savePost($_POST, $imagemUrl, (int)$_SESSION['user_id'], (string)($_SESSION['role'] ?? ''));
    flash('Publicação salva.');
} catch (Throwable $e) {
    flash('Não foi possível salvar: '.$e->getMessage(), 'danger');
}
redirect('admin/posts.php');
