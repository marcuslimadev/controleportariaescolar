<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_parent();

$studentId = (int)($_GET['id'] ?? 0);
$portalService = \App\Support\ServiceFactory::guardianPortal();
try {
    $detail = $portalService->childDetail((int)$_SESSION['responsavel_id'], $studentId, (string)($_GET['de'] ?? date('Y-m-01')), (string)($_GET['ate'] ?? date('Y-m-d')));
} catch (Throwable $error) {
    flash($error->getMessage(), 'warning');
    redirect('responsavel/index.php');
}

$child = $detail['child'];
$rows = $detail['movements'];
$summary = $detail['summary'];
$from = $detail['from'];
$to = $detail['to'];

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="historico-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower((string)$child['nome'])) . '-' . $from . '-a-' . $to . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Aluno', 'Turma', 'Movimento', 'Responsável', 'Data e hora'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['aluno'] ?? '',
            $r['turma'] ?? '',
            ($r['tipo'] ?? '') === 'saida' ? 'Saída' : 'Entrada',
            $r['responsavel'] ?? '',
            !empty($r['registrado_em']) ? date('d/m/Y H:i', strtotime($r['registrado_em'])) : '',
        ], ';');
    }
    exit;
}

layout_header('Detalhe do aluno');
?>
<div class="page-heading">
  <div>
    <span class="gate-eyebrow">ALUNO</span>
    <h1><?=e($child['nome'])?></h1>
    <p><?=e($child['turma'] ?? 'Sem turma')?></p>
  </div>
  <div class="page-actions">
    <a class="btn btn-outline-primary" href="<?=e(url('responsavel/index.php'))?>">Voltar</a>
  </div>
</div>

<section class="student-detail-card section-card">
  <?php if (!empty($child['foto'])): ?><img src="<?=e(media_url($child['foto'], $child['ultimo_registro'] ?? ''))?>" alt="Foto de <?=e($child['nome'])?>"><?php endif ?>
  <div>
    <span class="status-pill <?=$child['ultimo_tipo']==='entrada'?'active':'inactive'?>"><?=$child['ultimo_tipo']==='entrada'?'Dentro da escola':'Fora da escola'?></span>
    <h2><?=e($child['nome'])?></h2>
    <p><?=!empty($child['ultimo_registro']) ? 'Último registro: '.e(format_br_datetime($child['ultimo_registro'])) : 'Sem movimentação registrada'?></p>
  </div>
</section>

<section class="guardian-summary">
  <article><span>Movimentos</span><strong><?=e((string)$summary['movements'])?></strong></article>
  <article><span>Entradas</span><strong><?=e((string)$summary['entradas'])?></strong></article>
  <article><span>Saídas</span><strong><?=e((string)$summary['saidas'])?></strong></article>
  <article><span>Status</span><strong><?=e($child['ultimo_tipo']==='entrada'?'Dentro':'Fora')?></strong></article>
</section>

<form class="section-card row g-3 align-items-end mt-4">
  <input type="hidden" name="id" value="<?=(int)$child['id']?>">
  <div class="col-sm"><label class="form-label fw-bold">De</label><input type="date" class="form-control" name="de" value="<?=e($from)?>"></div>
  <div class="col-sm"><label class="form-label fw-bold">Até</label><input type="date" class="form-control" name="ate" value="<?=e($to)?>"></div>
  <div class="col-auto"><button class="btn btn-primary">Filtrar</button></div>
  <div class="col-auto"><a class="btn btn-outline-primary" href="<?=e(url('responsavel/aluno.php?id='.(int)$child['id'].'&de='.rawurlencode($from).'&ate='.rawurlencode($to).'&export=csv'))?>">Exportar CSV</a></div>
</form>

<?php if ($rows): ?>
  <section class="guardian-movement-list always-visible">
    <?php foreach ($rows as $r): ?>
      <article><div><strong><?=e($r['aluno'])?></strong><span><?=e($r['turma'] ?? 'Sem turma')?></span></div><span class="movement-pill <?=e($r['tipo'])?>"><?=$r['tipo']==='saida'?'Saída':'Entrada'?></span><small><?=e($r['responsavel'] ?? '-')?> · <?=e(format_br_datetime($r['registrado_em']))?></small></article>
    <?php endforeach ?>
  </section>
<?php else: ?>
  <div class="empty-state">Nenhuma movimentação no período selecionado.</div>
<?php endif ?>
<?php layout_footer();
