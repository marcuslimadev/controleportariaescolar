<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('post.manage');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/posts.php');
verify_csrf();

try {
    $imagemUrl = null;
    $anexoUrl = null;
    $anexoNome = null;
    if (!empty($_FILES['imagem'])) $imagemUrl = save_portal_upload($_FILES['imagem'], 'posts', 'image');
    if (!empty($_FILES['anexo'])) {
        $anexoUrl = save_portal_upload($_FILES['anexo'], 'posts/anexos');
        $anexoNome = $anexoUrl ? substr((string)($_FILES['anexo']['name'] ?? 'Anexo'), 0, 190) : null;
    }
    $service = \App\Support\ServiceFactory::posts();
    $service->savePost($_POST, $imagemUrl, $anexoUrl, $anexoNome, (int)$_SESSION['user_id'], (string)($_SESSION['role'] ?? ''));
    flash('Publicação salva.');
} catch (Throwable $e) {
    flash('Não foi possível salvar: '.$e->getMessage(), 'danger');
}
redirect('admin/posts.php');
