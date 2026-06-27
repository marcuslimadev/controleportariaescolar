<?php
require __DIR__ . '/../includes/bootstrap.php';
$isPublic = empty($_SESSION['user_id']) && empty($_SESSION['responsavel_id']);
$month = (string)($_GET['mes'] ?? date('Y-m'));
$turmaId = (int)($_GET['turma_id'] ?? 0);
if ($isPublic) {
    $visibilitySql = "p.publico='publico'";
    $visibilityParams = [];
    $turmaId = 0;
} else {
    [$visibilitySql, $visibilityParams] = post_visible_sql('p');
}
$postService = \App\Support\ServiceFactory::posts();
$eventData = $postService->eventsForMonth($month, $visibilitySql, $visibilityParams, $turmaId ?: null);
$month = $eventData['month'];
$events = $eventData['events'];
$turmas = $isPublic ? [] : $eventData['classes'];
layout_header(t('events'));
?>
<div class="page-heading"><div><span class="gate-eyebrow"><?=e(t('agenda'))?></span><h1><?=e(t('events_program'))?></h1><p><?=e(t('events_hint'))?></p></div><?php if($isPublic):?><div class="page-actions"><a class="btn btn-primary" href="<?=e(url('login.php'))?>"><?=e(t('login'))?></a></div><?php endif?></div>
<form class="section-card row g-3 align-items-end"><input type="hidden" name="lang" value="<?=e(current_locale())?>"><div class="col-md"><label class="form-label fw-bold"><?=e(t('month'))?></label><input type="month" class="form-control" name="mes" value="<?=e($month)?>"></div><?php if(!$isPublic):?><div class="col-md"><label class="form-label fw-bold"><?=e(t('class'))?></label><select class="form-select" name="turma_id"><option value="0"><?=e(t('all'))?></option><?php foreach($turmas as $t):?><option value="<?=$t['id']?>" <?=$turmaId===(int)$t['id']?'selected':''?>><?=e($t['nome'])?></option><?php endforeach?></select></div><?php endif?><div class="col-auto"><button class="btn btn-primary"><?=e(t('filter'))?></button></div></form>
<?php if($events): ?><section class="feed-list"><?php foreach($events as $ev):?><article class="feed-card event-card"><div class="feed-meta"><span class="post-type"><?=e($ev['tipo'])?></span><?=strtotime($ev['data_evento']) >= strtotime(date('Y-m-d')) ? '<span class="important-badge">'.e(t('upcoming')).'</span>' : '<span class="pin-badge">'.e(t('past')).'</span>'?></div><h2><?=e($ev['titulo'])?></h2><div class="event-strip"><strong><?=e(date('d/m/Y', strtotime($ev['data_evento'])))?></strong><?php if($ev['hora_evento']):?> às <?=e(substr($ev['hora_evento'],0,5))?><?php endif ?><?php if($ev['local']):?> · <?=e($ev['local'])?><?php endif ?><?php if($ev['turma']):?> · <?=e($ev['turma'])?><?php endif ?></div><p class="feed-content"><?=nl2br(e($ev['conteudo']))?></p></article><?php endforeach?></section><?php else:?><div class="empty-state"><?=e(t('no_events'))?></div><?php endif ?>
<?php layout_footer();
