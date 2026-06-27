<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('absence.read');
$absenceService = new \App\Services\AbsenceService(
    new \App\Infrastructure\Persistence\PdoAbsenceRepository(db()),
    new \App\Infrastructure\Logging\DatabaseAuditLogger()
);
$rows = (($_SESSION['role'] ?? '') === 'professor')
    ? $absenceService->listForTeacher(get_professor_id_for_user())
    : $absenceService->listForAdmin(null);
layout_header('Avisos da turma');
?>
<div class="page-heading"><div><span class="gate-eyebrow">PROFESSOR</span><h1>Avisos de falta</h1><p>Avisos enviados para alunos das suas turmas.</p></div></div>
<?php if($rows): ?><div class="data-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Data</th><th>Aluno</th><th>Turma</th><th>Motivo</th><th>Status</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e(date('d/m/Y', strtotime($r['data_falta'])))?></td><td><?=e($r['aluno'])?></td><td><?=e($r['turma'] ?: '-')?></td><td><?=e($r['motivo'])?><?php if($r['observacao']):?><br><small><?=e($r['observacao'])?></small><?php endif ?></td><td><span class="status-pill <?=$r['status']==='abonado'?'active':'inactive'?>"><?=e($r['status'])?></span></td></tr><?php endforeach?></tbody></table></div></div><?php else:?><div class="empty-state">Nenhum aviso de falta para suas turmas.</div><?php endif ?>
<?php layout_footer();
