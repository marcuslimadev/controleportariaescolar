<?php
require __DIR__ . '/../includes/bootstrap.php';
require_portal_access();

[$visibilitySql, $visibilityParams] = post_visible_sql('p');
$actorId = $_SESSION['responsavel_id'] ?? $_SESSION['user_id'];
$postService = \App\Support\ServiceFactory::posts();
$posts = $postService->feedPosts($visibilitySql, $visibilityParams, (int)$actorId, !empty($_SESSION['responsavel_id']));

layout_header(t('timeline'));
$quickActions = portal_quick_actions();
?>
<div class="page-heading">
  <div><span class="gate-eyebrow"><?=e(t('official_portal'))?></span><h1><?=e(t('timeline'))?></h1></div>
  <?php if (can_manage_posts()): ?><div class="page-actions"><a class="btn btn-primary" href="<?=e(url('admin/post-form.php'))?>"><?=e(t('new_post'))?></a><a class="btn btn-outline-primary" href="<?=e(url('admin/posts.php'))?>"><?=e(t('manage'))?></a></div><?php endif ?>
</div>

<?php if ($quickActions): ?>
  <section class="quick-action-grid" aria-label="<?=e(t('quick_actions'))?>">
    <?php foreach ($quickActions as [$label, $path, $hint]): ?>
      <a class="quick-action-card" href="<?=e(url($path))?>">
        <strong><?=e($label)?></strong>
        <span><?=e($hint)?></span>
      </a>
    <?php endforeach ?>
  </section>
<?php endif ?>

<?php if (!$posts): ?>
  <div class="empty-state"><?=e(t('no_posts'))?></div>
<?php endif ?>

<section class="feed-list">
<?php foreach ($posts as $post): ?>
  <article class="feed-card <?=$post['importante'] ? 'important' : ''?> <?=$post['fixado'] ? 'pinned' : ''?>">
    <div class="feed-meta">
      <span class="post-type"><?=e($post['tipo'])?></span>
      <?php if ($post['importante']): ?><span class="important-badge"><?=e(t('important'))?></span><?php endif ?>
      <?php if ($post['fixado']): ?><span class="pin-badge"><?=e(t('pinned'))?></span><?php endif ?>
    </div>
    <h2><?=e($post['titulo'])?></h2>
    <p class="feed-content"><?=nl2br(e(portal_excerpt($post['conteudo'])))?></p>
    <?php if ($post['imagem_url']): ?><img class="feed-image" src="<?=e($post['imagem_url'])?>" alt="<?=e(current_locale()==='en'?'Post image':'Imagem da publicação')?>"><?php endif ?>
    <?php if ($post['data_evento']): ?>
      <div class="event-strip"><strong><?=e(date('d/m/Y', strtotime($post['data_evento'])))?></strong><?php if ($post['hora_evento']): ?> às <?=e(substr($post['hora_evento'], 0, 5))?><?php endif ?><?php if ($post['local']): ?> · <?=e($post['local'])?><?php endif ?></div>
    <?php endif ?>
    <div class="feed-footer">
      <span><?=e($post['autor'])?> · <?=e(format_br_datetime($post['publicado_em'] ?: $post['created_at']))?></span>
    </div>
    <?php if ($post['exige_ciencia']): ?>
      <?php if ($post['ciencia_em']): ?>
        <div class="science-box confirmed"><?=e(t('science_confirmed'))?> <?=e(format_br_datetime($post['ciencia_em']))?></div>
      <?php else: ?>
        <form method="post" action="<?=e(url('post-ciencia.php'))?>" class="mt-3">
          <input type="hidden" name="csrf" value="<?=e(csrf())?>">
          <input type="hidden" name="post_id" value="<?=(int)$post['id']?>">
          <button class="btn btn-primary w-100 btn-lg" type="submit"><?=e(t('acknowledge'))?></button>
        </form>
      <?php endif ?>
    <?php endif ?>
  </article>
<?php endforeach ?>
</section>
<?php layout_footer();
