<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_role(['admin','secretaria','professor']);
$params = [];
$where = '1=1';
if (($_SESSION['role'] ?? '') === 'professor') {
    $professorId = get_professor_id_for_user();
    $where = 'af.turma_id IN (SELECT turma_id FROM scp_professor_turma WHERE professor_id=?)';
    $params[] = $professorId;
}
$q = db()->prepare("SELECT af.*, a.nome aluno, t.nome turma, r.nome responsavel FROM scp_avisos_falta af JOIN scp_alunos a ON a.id=af.aluno_id LEFT JOIN scp_turmas t ON t.id=af.turma_id JOIN scp_responsaveis r ON r.id=af.responsavel_id WHERE $where ORDER BY af.data_falta DESC, af.id DESC");
$q->execute($params);
$rows = $q->fetchAll();
layout_header('Avisos da turma');
?>
<div class="page-heading"><div><span class="gate-eyebrow">PROFESSOR</span><h1>Avisos de falta</h1><p>Avisos enviados para alunos das suas turmas.</p></div></div>
<?php if($rows): ?><div class="data-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Data</th><th>Aluno</th><th>Turma</th><th>Motivo</th><th>Status</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e(date('d/m/Y', strtotime($r['data_falta'])))?></td><td><?=e($r['aluno'])?></td><td><?=e($r['turma'] ?: '-')?></td><td><?=e($r['motivo'])?><?php if($r['observacao']):?><br><small><?=e($r['observacao'])?></small><?php endif ?></td><td><span class="status-pill <?=$r['status']==='abonado'?'active':'inactive'?>"><?=e($r['status'])?></span></td></tr><?php endforeach?></tbody></table></div></div><?php else:?><div class="empty-state">Nenhum aviso de falta para suas turmas.</div><?php endif ?>
<?php layout_footer();
