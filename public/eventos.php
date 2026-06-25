<?php
require __DIR__ . '/../includes/bootstrap.php';
require_portal_access();
$month = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['mes'] ?? '')) ? $_GET['mes'] : date('Y-m');
$start = $month . '-01';
$end = date('Y-m-t', strtotime($start));
$turmaId = (int)($_GET['turma_id'] ?? 0);
[$visibilitySql, $visibilityParams] = post_visible_sql('p');
$params = array_merge([$start, $end], $visibilityParams);
$extra = '';
if ($turmaId) { $extra = ' AND (p.turma_id=? OR p.turma_id IS NULL)'; $params[] = $turmaId; }
$q = db()->prepare("SELECT p.*, u.nome autor, t.nome turma FROM scp_posts p JOIN scp_usuarios u ON u.id=p.autor_id LEFT JOIN scp_turmas t ON t.id=p.turma_id WHERE p.status='publicado' AND p.tipo IN ('evento','programação') AND p.data_evento BETWEEN ? AND ? AND $visibilitySql $extra ORDER BY p.data_evento ASC, p.hora_evento ASC");
$q->execute($params);
$events = $q->fetchAll();
$turmas = db()->query('SELECT id,nome FROM scp_turmas WHERE ativo=1 ORDER BY nome')->fetchAll();
layout_header('Eventos');
?>
<div class="page-heading"><div><span class="gate-eyebrow">AGENDA</span><h1>Eventos e programação</h1><p>Próximos eventos e programação oficial da escola.</p></div></div>
<form class="section-card row g-3 align-items-end"><div class="col-md"><label class="form-label fw-bold">Mês</label><input type="month" class="form-control" name="mes" value="<?=e($month)?>"></div><div class="col-md"><label class="form-label fw-bold">Turma</label><select class="form-select" name="turma_id"><option value="0">Todas</option><?php foreach($turmas as $t):?><option value="<?=$t['id']?>" <?=$turmaId===(int)$t['id']?'selected':''?>><?=e($t['nome'])?></option><?php endforeach?></select></div><div class="col-auto"><button class="btn btn-primary">Filtrar</button></div></form>
<?php if($events): ?><section class="feed-list"><?php foreach($events as $ev):?><article class="feed-card event-card"><div class="feed-meta"><span class="post-type"><?=e($ev['tipo'])?></span><?=strtotime($ev['data_evento']) >= strtotime(date('Y-m-d')) ? '<span class="important-badge">Próximo</span>' : '<span class="pin-badge">Passado</span>'?></div><h2><?=e($ev['titulo'])?></h2><div class="event-strip"><strong><?=e(date('d/m/Y', strtotime($ev['data_evento'])))?></strong><?php if($ev['hora_evento']):?> às <?=e(substr($ev['hora_evento'],0,5))?><?php endif ?><?php if($ev['local']):?> · <?=e($ev['local'])?><?php endif ?><?php if($ev['turma']):?> · <?=e($ev['turma'])?><?php endif ?></div><p class="feed-content"><?=nl2br(e($ev['conteudo']))?></p></article><?php endforeach?></section><?php else:?><div class="empty-state">Nenhum evento neste mês.</div><?php endif ?>
<?php layout_footer();
