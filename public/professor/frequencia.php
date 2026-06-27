<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('frequency.manage');
$frequencyService = \App\Support\ServiceFactory::frequency();
$role = (string)($_SESSION['role'] ?? '');
$professorId = $role === 'professor' ? get_professor_id_for_user() : 0;
$date = (string)($_GET['data'] ?? date('Y-m-d'));
$turmaId = (int)($_GET['turma_id'] ?? 0);
$situacaoFiltro = (string)($_GET['situacao'] ?? '');
$nome = trim((string)($_GET['nome'] ?? ''));

$turmas = $frequencyService->classesForActor($role, $professorId);
if (!$turmaId && $turmas) $turmaId = (int)$turmas[0]['id'];
$rows = $frequencyService->dailyReport($date, $turmaId ?: null, $nome, $role, $professorId, $situacaoFiltro);
layout_header('Frequência');
?>
<div class="page-heading"><div><span class="gate-eyebrow">FREQUÊNCIA</span><h1>Frequência da turma</h1><p>Situação calculada por registros da portaria e avisos de falta.</p></div></div>
<form class="section-card row g-3 align-items-end"><div class="col-md-3"><label class="form-label fw-bold">Data</label><input type="date" class="form-control" name="data" value="<?=e($date)?>"></div><div class="col-md-3"><label class="form-label fw-bold">Turma</label><select class="form-select" name="turma_id"><?php foreach($turmas as $t):?><option value="<?=$t['id']?>" <?=$turmaId===(int)$t['id']?'selected':''?>><?=e($t['nome'])?></option><?php endforeach?></select></div><div class="col-md-3"><label class="form-label fw-bold">Situação</label><select class="form-select" name="situacao"><option value="">Todas</option><?php foreach(\App\Services\FrequencyService::SITUATIONS as $s):?><option value="<?=e($s)?>" <?=$situacaoFiltro===$s?'selected':''?>><?=e($s)?></option><?php endforeach?></select></div><div class="col-md-2"><label class="form-label fw-bold">Aluno</label><input class="form-control" name="nome" value="<?=e($nome)?>"></div><div class="col-auto"><button class="btn btn-primary">Filtrar</button></div></form>
<?php if($rows): ?><div class="data-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Aluno</th><th>Situação</th><th>Última entrada</th><th>Última saída</th><th>Aviso de falta</th><th>Observação</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['nome'])?><br><small><?=e($r['turma'] ?: '-')?></small></td><td><span class="status-pill <?=$r['situacao']==='presente'?'active':'inactive'?>"><?=e($r['situacao'])?></span></td><td><?=e(format_br_datetime($r['ultima_entrada']))?></td><td><?=e(format_br_datetime($r['ultima_saida']))?></td><td><?=e($r['aviso_texto'] ?: '-')?></td><td><?=e($r['situacao']==='sem registro' ? 'Sem movimentação no dia' : '')?></td></tr><?php endforeach?></tbody></table></div></div><?php else:?><div class="empty-state">Nenhum aluno encontrado para os filtros.</div><?php endif ?>
<?php layout_footer();
