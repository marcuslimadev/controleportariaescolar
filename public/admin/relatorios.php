<?php
require __DIR__.'/../../includes/bootstrap.php';
require_role(['admin']);
$reportService = \App\Support\ServiceFactory::reports();
$report = $reportService->accessMovements((string)($_GET['de'] ?? date('Y-m-d')), (string)($_GET['ate'] ?? date('Y-m-d')));
$de = $report['from']; $ate = $report['to']; $rows = $report['rows'];
layout_header('Relatórios');?>
<div class="page-heading"><div><span class="gate-eyebrow">RELATÓRIOS</span><h1>Movimentações</h1><p>Histórico de entradas e saídas na portaria</p></div></div>
<form class="section-card row g-3 align-items-end"><div class="col-sm"><label class="form-label fw-bold">De</label><input type="date" class="form-control" name="de" value="<?=e($de)?>"></div><div class="col-sm"><label class="form-label fw-bold">Até</label><input type="date" class="form-control" name="ate" value="<?=e($ate)?>"></div><div class="col-auto"><button class="btn btn-primary">Filtrar</button></div></form>
<?php if($rows):?><div class="data-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Data</th><th>Aluno</th><th>Turma</th><th>Tipo</th><th>Portaria</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e(date('d/m/Y H:i',strtotime($r['registrado_em'])))?></td><td><?=e($r['aluno'])?></td><td><?=e($r['turma']??'-')?></td><td><span class="movement-pill <?=e($r['tipo'])?>"><?=$r['tipo']==='saida'?'Saída':'Entrada'?></span></td><td><?=e($r['usuario'])?></td></tr><?php endforeach?></tbody></table></div></div><?php else:?><div class="empty-state">Nenhuma movimentação no período selecionado.</div><?php endif?><?php layout_footer();

