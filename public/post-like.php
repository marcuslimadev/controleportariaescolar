<?php
require __DIR__ . '/../includes/bootstrap.php';
require_portal_access();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('feed.php');
verify_csrf();
$postId = (int)($_POST['post_id'] ?? 0);
[$visibilitySql, $visibilityParams] = post_visible_sql('p');
$q = db()->prepare("SELECT p.id FROM scp_posts p WHERE p.id=? AND p.status='publicado' AND $visibilitySql");
$q->execute(array_merge([$postId], $visibilityParams));
if (!$q->fetchColumn()) { flash('Publicação não encontrada.', 'warning'); redirect('feed.php'); }

if (!empty($_SESSION['responsavel_id'])) {
    $check = db()->prepare('SELECT id FROM scp_post_curtidas WHERE post_id=? AND responsavel_id=?');
    $check->execute([$postId, $_SESSION['responsavel_id']]);
    $id = (int)$check->fetchColumn();
    if ($id) db()->prepare('DELETE FROM scp_post_curtidas WHERE id=?')->execute([$id]);
    else db()->prepare('INSERT INTO scp_post_curtidas(post_id,responsavel_id) VALUES(?,?)')->execute([$postId, $_SESSION['responsavel_id']]);
} else {
    $check = db()->prepare('SELECT id FROM scp_post_curtidas WHERE post_id=? AND usuario_id=?');
    $check->execute([$postId, $_SESSION['user_id']]);
    $id = (int)$check->fetchColumn();
    if ($id) db()->prepare('DELETE FROM scp_post_curtidas WHERE id=?')->execute([$id]);
    else db()->prepare('INSERT INTO scp_post_curtidas(post_id,usuario_id) VALUES(?,?)')->execute([$postId, $_SESSION['user_id']]);
}
audit('alternar_curtida_post', 'scp_posts', $postId);
redirect('feed.php');
