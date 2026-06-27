<?php
require __DIR__.'/../../includes/bootstrap.php';
require_parent();
$portalService = new \App\Services\GuardianPortalService(
    new \App\Infrastructure\Persistence\PdoGuardianPortalRepository(db())
);
$dashboard = $portalService->dashboard((int)$_SESSION['responsavel_id'], (string)($_GET['de'] ?? date('Y-m-01')), (string)($_GET['ate'] ?? date('Y-m-d')));
$from = $dashboard['from']; $to = $dashboard['to']; $rows = $dashboard['movements']; $children = $dashboard['children'];
layout_header('Portal do responsável');?>
<div class="page-heading"><div><span class="gate-eyebrow">PORTAL DA FAMÍLIA</span><h1>Olá, <?=e($_SESSION['name'])?></h1><p>Seus alunos e movimentações</p></div><div class="page-actions"><a class="btn btn-primary" href="<?=e(url('cracha.php'))?>">Abrir meu crachá</a></div></div>
<div class="portal-cards">
<?php if($children):?><div class="family-cards"><?php foreach($children as $child):?><article><?php if($child['foto']):?><img src="<?=e($child['foto'])?>" alt="Foto de <?=e($child['nome'])?>"><?php endif?><div><strong><?=e($child['nome'])?></strong><span><?=e($child['turma']??'Sem turma')?></span></div></article><?php endforeach?></div><?php else:?><div class="empty-state">Nenhum aluno vinculado à sua conta.</div><?php endif?>
<form class="section-card row g-3 align-items-end mt-4"><div class="col-sm"><label class="form-label fw-bold">De</label><input type="date" class="form-control" name="de" value="<?=e($from)?>"></div><div class="col-sm"><label class="form-label fw-bold">Até</label><input type="date" class="form-control" name="ate" value="<?=e($to)?>"></div><div class="col-auto"><button class="btn btn-primary">Filtrar</button></div></form>
<?php if($rows):?><div class="data-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Aluno</th><th>Turma</th><th>Movimento</th><th>Retirado por</th><th>Data e hora</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['aluno'])?></td><td><?=e($r['turma']??'-')?></td><td><?=$r['tipo']?'<span class="movement-pill '.e($r['tipo']).'">'.($r['tipo']==='saida'?'Saída':'Entrada').'</span>':'-'?></td><td><?=e($r['responsavel']??'-')?></td><td><?=e($r['registrado_em']?date('d/m/Y H:i',strtotime($r['registrado_em'])):'Sem registros')?></td></tr><?php endforeach?></tbody></table></div></div><?php else:?><div class="empty-state">Nenhuma movimentação no período selecionado.</div><?php endif?>
</div><?php layout_footer();
