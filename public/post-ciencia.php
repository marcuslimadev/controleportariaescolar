<?php
require __DIR__ . '/../includes/bootstrap.php';
require_portal_access();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('feed.php');
verify_csrf();
$postId = (int)($_POST['post_id'] ?? 0);
[$visibilitySql, $visibilityParams] = post_visible_sql('p');
$q = db()->prepare("SELECT p.id FROM scp_posts p WHERE p.id=? AND p.status='publicado' AND p.deleted_at IS NULL AND p.exige_ciencia=1 AND $visibilitySql");
$q->execute(array_merge([$postId], $visibilityParams));
if (!$q->fetchColumn()) { flash('Comunicado não encontrado.', 'warning'); redirect('feed.php'); }

if (!empty($_SESSION['responsavel_id'])) {
    $q = db()->prepare('INSERT IGNORE INTO scp_post_ciencias(post_id,responsavel_id,ip,user_agent) VALUES(?,?,?,?)');
    $q->execute([$postId, $_SESSION['responsavel_id'], $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)]);
} else {
    $q = db()->prepare('INSERT IGNORE INTO scp_post_ciencias(post_id,usuario_id,ip,user_agent) VALUES(?,?,?,?)');
    $q->execute([$postId, $_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)]);
}
audit('confirmar_ciencia_post', 'scp_posts', $postId);
flash('Ciência confirmada.');
redirect('feed.php');
