<?php
require __DIR__ . '/../includes/bootstrap.php';
require_portal_access();
$service = \App\Support\ServiceFactory::notifications();
$rows = $service->listForActor($_SESSION['user_id'] ?? null, $_SESSION['responsavel_id'] ?? null);
layout_header(current_locale()==='en' ? 'Notifications' : 'Notificações');
?>
<div class="page-heading">
  <div>
    <span class="gate-eyebrow"><?=e(current_locale()==='en' ? 'UPDATES' : 'ATUALIZAÇÕES')?></span>
    <h1><?=e(current_locale()==='en' ? 'Notifications' : 'Notificações')?></h1>
    <p><?=e(current_locale()==='en' ? 'Recent internal notices from the school.' : 'Avisos internos recentes da escola.')?></p>
  </div>
  <?php if ($rows): ?>
    <form method="post" action="<?=e(url('notificacoes-ler.php'))?>" class="page-actions">
      <input type="hidden" name="csrf" value="<?=e(csrf())?>">
      <button class="btn btn-outline-primary" type="submit"><?=e(current_locale()==='en' ? 'Mark all as read' : 'Marcar todas como lidas')?></button>
    </form>
  <?php endif ?>
</div>

<?php if ($rows): ?>
  <section class="notification-list">
    <?php foreach ($rows as $row): ?>
      <a class="notification-card <?=$row['lida_em'] ? 'is-read' : 'is-unread'?>" href="<?=e(url($row['link'] ?: 'feed.php'))?>">
        <span class="notification-dot"></span>
        <div>
          <strong><?=e($row['titulo'])?></strong>
          <p><?=e($row['mensagem'])?></p>
          <small><?=e(format_br_datetime($row['created_at']))?></small>
        </div>
      </a>
    <?php endforeach ?>
  </section>
<?php else: ?>
  <div class="empty-state"><?=e(current_locale()==='en' ? 'No notifications yet.' : 'Nenhuma notificação por enquanto.')?></div>
<?php endif ?>
<?php layout_footer();
