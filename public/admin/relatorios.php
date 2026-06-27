<?php
require __DIR__.'/../../includes/bootstrap.php';
require_role(['admin']);
$reportService = \App\Support\ServiceFactory::reports();
$report = $reportService->accessMovements((string)($_GET['de'] ?? date('Y-m-d')), (string)($_GET['ate'] ?? date('Y-m-d')));
$de = $report['from']; $ate = $report['to']; $rows = $report['rows']; $summary = $report['summary'];
if (($_GET['export'] ?? '') === 'csv') {
    $filename = 'movimentacoes-' . $de . '-a-' . $ate . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Data', 'Aluno', 'Turma', 'Tipo', 'Portaria', 'Origem', 'Manual', 'Observação'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [
            date('d/m/Y H:i', strtotime($r['registrado_em'])),
            $r['aluno'] ?? '',
            $r['turma'] ?? '',
            ($r['tipo'] ?? '') === 'saida' ? 'Saída' : 'Entrada',
            $r['usuario'] ?? '',
            $r['origem'] ?? '',
            !empty($r['manual']) ? 'Sim' : 'Não',
            $r['observacao'] ?? '',
        ], ';');
    }
    exit;
}
layout_header('Relatórios');?>
<div class="page-heading"><div><span class="gate-eyebrow">RELATÓRIOS</span><h1>Movimentações</h1><p>Histórico de entradas e saídas na portaria</p></div></div>
<section class="report-metrics">
  <article><span>Total</span><strong><?=e((string)$summary['total'])?></strong></article>
  <article><span>Entradas</span><strong><?=e((string)$summary['entradas'])?></strong></article>
  <article><span>Saídas</span><strong><?=e((string)$summary['saidas'])?></strong></article>
  <article><span>Alunos</span><strong><?=e((string)$summary['alunos'])?></strong></article>
</section>
<form class="section-card row g-3 align-items-end">
  <div class="col-sm"><label class="form-label fw-bold">De</label><input type="date" class="form-control" name="de" value="<?=e($de)?>"></div>
  <div class="col-sm"><label class="form-label fw-bold">Até</label><input type="date" class="form-control" name="ate" value="<?=e($ate)?>"></div>
  <div class="col-auto"><button class="btn btn-primary">Filtrar</button></div>
  <div class="col-auto"><a class="btn btn-outline-primary" href="<?=e(url('admin/relatorios.php?de='.rawurlencode($de).'&ate='.rawurlencode($ate).'&export=csv'))?>">Exportar CSV</a></div>
</form>
<?php if($rows):?><div class="data-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Data</th><th>Aluno</th><th>Turma</th><th>Tipo</th><th>Portaria</th><th>Origem</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e(date('d/m/Y H:i',strtotime($r['registrado_em'])))?></td><td><?=e($r['aluno'])?></td><td><?=e($r['turma']??'-')?></td><td><span class="movement-pill <?=e($r['tipo'])?>"><?=$r['tipo']==='saida'?'Saída':'Entrada'?></span></td><td><?=e($r['usuario'])?></td><td><?=e($r['origem'] ?? '-')?><?=!empty($r['manual'])?' · Manual':''?></td></tr><?php endforeach?></tbody></table></div></div><?php else:?><div class="empty-state">Nenhuma movimentação no período selecionado.</div><?php endif?><?php layout_footer();

