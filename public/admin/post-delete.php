<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_role(['admin','secretaria']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/posts.php');
verify_csrf();

try {
    $id = (int)($_POST['id'] ?? 0);
    $q = db()->prepare('SELECT p.*, u.perfil autor_perfil FROM scp_posts p JOIN scp_usuarios u ON u.id=p.autor_id WHERE p.id=?');
    $q->execute([$id]);
    $post = $q->fetch();
    if (!$post) throw new RuntimeException('Publicação não encontrada.');
    if (($_SESSION['role'] ?? '') === 'secretaria' && (int)$post['autor_id'] !== (int)$_SESSION['user_id'] && $post['autor_perfil'] !== 'secretaria') {
        throw new RuntimeException('Você só pode excluir publicações da secretaria ou criadas por você.');
    }

    db()->prepare('DELETE FROM scp_posts WHERE id=?')->execute([$id]);
    audit('excluir_post', 'scp_posts', $id, ['titulo'=>$post['titulo'], 'status'=>$post['status']]);
    flash('Publicação excluída.');
} catch (Throwable $e) {
    flash('Não foi possível excluir: '.$e->getMessage(), 'danger');
}

redirect('admin/posts.php');
