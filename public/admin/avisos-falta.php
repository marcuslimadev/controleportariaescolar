<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('absence.manage');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');
    if (in_array($status, ['visualizado','abonado','rejeitado'], true)) {
        $sql = $status === 'visualizado'
            ? 'UPDATE scp_avisos_falta SET status=?, visualizado_em=COALESCE(visualizado_em,NOW()), analisado_por=?, analisado_em=NOW() WHERE id=?'
            : 'UPDATE scp_avisos_falta SET status=?, visualizado_em=COALESCE(visualizado_em,NOW()), analisado_por=?, analisado_em=NOW() WHERE id=?';
        $q = db()->prepare($sql);
        $q->execute([$status, $_SESSION['user_id'], $id]);
        audit('alterar_aviso_falta', 'scp_avisos_falta', $id, ['status'=>$status]);
        flash('Aviso atualizado.');
    }
    redirect('admin/avisos-falta.php');
}
$status = $_GET['status'] ?? '';
$params = [];
$where = '';
if (in_array($status, ['enviado','visualizado','abonado','rejeitado'], true)) { $where = 'WHERE af.status=?'; $params[] = $status; }
$q = db()->prepare("SELECT af.*, a.nome aluno, t.nome turma, r.nome responsavel FROM scp_avisos_falta af JOIN scp_alunos a ON a.id=af.aluno_id LEFT JOIN scp_turmas t ON t.id=af.turma_id JOIN scp_responsaveis r ON r.id=af.responsavel_id $where ORDER BY af.data_falta DESC, af.id DESC");
$q->execute($params);
$rows = $q->fetchAll();
layout_header('Avisos de falta');
?>
<div class="page-heading"><div><span class="gate-eyebrow">SECRETARIA</span><h1>Avisos de falta</h1><p>Visualize, abone ou rejeite avisos enviados pelos responsáveis.</p></div></div>
<?php if($rows): ?><div class="data-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Aluno</th><th>Responsável</th><th>Data</th><th>Motivo</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['aluno'])?><br><small><?=e($r['turma'] ?: '-')?></small></td><td><?=e($r['responsavel'])?></td><td><?=e(date('d/m/Y', strtotime($r['data_falta'])))?></td><td><?=e($r['motivo'])?><?php if($r['observacao']):?><br><small><?=e($r['observacao'])?></small><?php endif ?><?php if($r['anexo_url']):?><br><a href="<?=e($r['anexo_url'])?>" target="_blank">Abrir anexo</a><?php endif ?></td><td><span class="status-pill <?=$r['status']==='abonado'?'active':'inactive'?>"><?=e($r['status'])?></span></td><td class="text-end"><?php foreach(['visualizado'=>'Visualizar','abonado'=>'Abonar','rejeitado'=>'Rejeitar'] as $s=>$label):?><form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="id" value="<?=(int)$r['id']?>"><input type="hidden" name="status" value="<?=e($s)?>"><button class="btn btn-sm btn-outline-primary"><?=$label?></button></form> <?php endforeach ?></td></tr><?php endforeach?></tbody></table></div></div><?php else:?><div class="empty-state">Nenhum aviso encontrado.</div><?php endif ?>
<?php layout_footer();
