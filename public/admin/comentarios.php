<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('post.manage');
$service = \App\Support\ServiceFactory::postInteractions();
$rows = $service->pendingComments();
layout_header('Comentários');
?>
<div class="page-heading">
  <div>
    <span class="gate-eyebrow">COMUNICAÇÃO</span>
    <h1>Comentários pendentes</h1>
    <p>Aprove apenas comentários adequados para aparecerem no feed privado.</p>
  </div>
  <div class="page-actions"><a class="btn btn-outline-primary" href="<?=e(url('admin/posts.php'))?>">Publicações</a></div>
</div>

<?php if ($rows): ?>
  <div class="comments-moderation-list">
    <?php foreach ($rows as $row): ?>
      <article class="section-card comment-moderation-card">
        <div class="section-title-row">
          <div>
            <h2><?=e($row['autor'] ?: 'Usuário')?></h2>
            <p class="text-muted mb-0"><?=e($row['perfil'] ?: '-')?> · <?=e(format_br_datetime($row['created_at']))?> · <?=e($row['post_titulo'])?></p>
          </div>
          <span class="pin-badge">Pendente</span>
        </div>
        <p class="comment-moderation-text"><?=nl2br(e($row['comentario']))?></p>
        <div class="page-actions">
          <form method="post" action="<?=e(url('admin/comentario-status.php'))?>">
            <input type="hidden" name="csrf" value="<?=e(csrf())?>">
            <input type="hidden" name="id" value="<?=(int)$row['id']?>">
            <input type="hidden" name="status" value="aprovado">
            <button class="btn btn-primary" type="submit">Aprovar</button>
          </form>
          <form method="post" action="<?=e(url('admin/comentario-status.php'))?>">
            <input type="hidden" name="csrf" value="<?=e(csrf())?>">
            <input type="hidden" name="id" value="<?=(int)$row['id']?>">
            <input type="hidden" name="status" value="rejeitado">
            <button class="btn btn-outline-secondary" type="submit">Rejeitar</button>
          </form>
        </div>
      </article>
    <?php endforeach ?>
  </div>
<?php else: ?>
  <div class="empty-state">Nenhum comentário pendente.</div>
<?php endif ?>
<?php layout_footer();
