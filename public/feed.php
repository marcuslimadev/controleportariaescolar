<?php
require __DIR__ . '/../includes/bootstrap.php';
require_portal_access();

[$visibilitySql, $visibilityParams] = post_visible_sql('p');
$params = $visibilityParams;
$sql = "SELECT p.*, u.nome autor,
        (SELECT COUNT(*) FROM scp_post_curtidas c WHERE c.post_id=p.id) curtidas,
        (SELECT COUNT(*) FROM scp_post_curtidas c WHERE c.post_id=p.id AND " . (!empty($_SESSION['responsavel_id']) ? 'c.responsavel_id=?' : 'c.usuario_id=?') . ") curtiu,
        (SELECT confirmado_em FROM scp_post_ciencias ci WHERE ci.post_id=p.id AND " . (!empty($_SESSION['responsavel_id']) ? 'ci.responsavel_id=?' : 'ci.usuario_id=?') . " LIMIT 1) ciencia_em
        FROM scp_posts p
        JOIN scp_usuarios u ON u.id=p.autor_id
        WHERE p.status='publicado' AND p.deleted_at IS NULL AND $visibilitySql
        ORDER BY p.fixado DESC, p.publicado_em DESC, p.id DESC
        LIMIT 80";
$actorId = $_SESSION['responsavel_id'] ?? $_SESSION['user_id'];
array_unshift($params, $actorId, $actorId);
$q = db()->prepare($sql);
$q->execute($params);
$posts = $q->fetchAll();

layout_header('Timeline oficial');
$quickActions = portal_quick_actions();
?>
<div class="page-heading">
  <div><span class="gate-eyebrow">PORTAL OFICIAL</span><h1>Timeline</h1></div>
  <?php if (can_manage_posts()): ?><div class="page-actions"><a class="btn btn-primary" href="<?=e(url('admin/post-form.php'))?>">Nova publicação</a><a class="btn btn-outline-primary" href="<?=e(url('admin/posts.php'))?>">Gerenciar</a></div><?php endif ?>
</div>

<?php if ($quickActions): ?>
  <section class="quick-action-grid" aria-label="Ações rápidas">
    <?php foreach ($quickActions as [$label, $path, $hint]): ?>
      <a class="quick-action-card" href="<?=e(url($path))?>">
        <strong><?=e($label)?></strong>
        <span><?=e($hint)?></span>
      </a>
    <?php endforeach ?>
  </section>
<?php endif ?>

<?php if (!$posts): ?>
  <div class="empty-state">Nenhuma publicação disponível no momento.</div>
<?php endif ?>

<section class="feed-list">
<?php foreach ($posts as $post): ?>
  <article class="feed-card <?=$post['importante'] ? 'important' : ''?> <?=$post['fixado'] ? 'pinned' : ''?>">
    <div class="feed-meta">
      <span class="post-type"><?=e($post['tipo'])?></span>
      <?php if ($post['importante']): ?><span class="important-badge">Importante</span><?php endif ?>
      <?php if ($post['fixado']): ?><span class="pin-badge">Fixado</span><?php endif ?>
    </div>
    <h2><?=e($post['titulo'])?></h2>
    <p class="feed-content"><?=nl2br(e(portal_excerpt($post['conteudo'])))?></p>
    <?php if ($post['imagem_url']): ?><img class="feed-image" src="<?=e($post['imagem_url'])?>" alt="Imagem da publicação"><?php endif ?>
    <?php if ($post['data_evento']): ?>
      <div class="event-strip"><strong><?=e(date('d/m/Y', strtotime($post['data_evento'])))?></strong><?php if ($post['hora_evento']): ?> às <?=e(substr($post['hora_evento'], 0, 5))?><?php endif ?><?php if ($post['local']): ?> · <?=e($post['local'])?><?php endif ?></div>
    <?php endif ?>
    <div class="feed-footer">
      <span><?=e($post['autor'])?> · <?=e(format_br_datetime($post['publicado_em'] ?: $post['created_at']))?></span>
      <form method="post" action="<?=e(url('post-like.php'))?>">
        <input type="hidden" name="csrf" value="<?=e(csrf())?>">
        <input type="hidden" name="post_id" value="<?=(int)$post['id']?>">
        <button class="like-button <?=$post['curtiu'] ? 'liked' : ''?>" type="submit"><?=$post['curtiu'] ? 'Curtido' : 'Curtir'?> · <?=(int)$post['curtidas']?></button>
      </form>
    </div>
    <?php if ($post['exige_ciencia']): ?>
      <?php if ($post['ciencia_em']): ?>
        <div class="science-box confirmed">Ciência confirmada em <?=e(format_br_datetime($post['ciencia_em']))?></div>
      <?php else: ?>
        <form method="post" action="<?=e(url('post-ciencia.php'))?>" class="mt-3">
          <input type="hidden" name="csrf" value="<?=e(csrf())?>">
          <input type="hidden" name="post_id" value="<?=(int)$post['id']?>">
          <button class="btn btn-primary w-100 btn-lg" type="submit">Li e estou ciente</button>
        </form>
      <?php endif ?>
    <?php endif ?>
  </article>
<?php endforeach ?>
</section>
<?php layout_footer();
