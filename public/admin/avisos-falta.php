<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('absence.manage');
$absenceService = \App\Support\ServiceFactory::absences();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $absenceService->updateStatus((int)($_POST['id'] ?? 0), (string)($_POST['status'] ?? ''), (int)$_SESSION['user_id']);
        flash('Aviso atualizado.');
    } catch (Throwable $error) {
        flash('Não foi possível atualizar: '.$error->getMessage(), 'danger');
    }
    redirect('admin/avisos-falta.php');
}
$status = $_GET['status'] ?? '';
$rows = $absenceService->listForAdmin(is_string($status) ? $status : null);
layout_header('Avisos de falta');
?>
<div class="page-heading"><div><span class="gate-eyebrow">SECRETARIA</span><h1>Avisos de falta</h1><p>Visualize, abone ou rejeite avisos enviados pelos responsáveis.</p></div></div>
<?php if($rows): ?><div class="data-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Aluno</th><th>Responsável</th><th>Data</th><th>Motivo</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['aluno'])?><br><small><?=e($r['turma'] ?: '-')?></small></td><td><?=e($r['responsavel'])?></td><td><?=e(date('d/m/Y', strtotime($r['data_falta'])))?></td><td><?=e($r['motivo'])?><?php if($r['observacao']):?><br><small><?=e($r['observacao'])?></small><?php endif ?><?php if($r['anexo_url']):?><br><a href="<?=e($r['anexo_url'])?>" target="_blank">Abrir anexo</a><?php endif ?></td><td><span class="status-pill <?=$r['status']==='abonado'?'active':'inactive'?>"><?=e($r['status'])?></span></td><td class="text-end"><?php foreach(['visualizado'=>'Visualizar','abonado'=>'Abonar','rejeitado'=>'Rejeitar'] as $s=>$label):?><form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="id" value="<?=(int)$r['id']?>"><input type="hidden" name="status" value="<?=e($s)?>"><button class="btn btn-sm btn-outline-primary"><?=$label?></button></form> <?php endforeach ?></td></tr><?php endforeach?></tbody></table></div></div><?php else:?><div class="empty-state">Nenhum aviso encontrado.</div><?php endif ?>
<?php layout_footer();
