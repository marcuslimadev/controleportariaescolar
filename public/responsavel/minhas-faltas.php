<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_parent();
$q = db()->prepare('SELECT af.*, a.nome aluno, t.nome turma FROM scp_avisos_falta af JOIN scp_alunos a ON a.id=af.aluno_id LEFT JOIN scp_turmas t ON t.id=af.turma_id WHERE af.responsavel_id=? ORDER BY af.data_falta DESC, af.id DESC');
$q->execute([$_SESSION['responsavel_id']]);
$rows = $q->fetchAll();
layout_header('Minhas faltas');
?>
<div class="page-heading"><div><span class="gate-eyebrow">FREQUÊNCIA</span><h1>Meus avisos de falta</h1><p>Acompanhe o status dos avisos enviados.</p></div><div class="page-actions"><a class="btn btn-primary" href="<?=e(url('responsavel/avisar-falta.php'))?>">Novo aviso</a></div></div>
<?php if($rows): ?><div class="data-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Aluno</th><th>Data</th><th>Motivo</th><th>Status</th><th>Anexo</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['aluno'])?><br><small><?=e($r['turma'] ?: '-')?></small></td><td><?=e(date('d/m/Y', strtotime($r['data_falta'])))?></td><td><?=e($r['motivo'])?></td><td><span class="status-pill <?=$r['status']==='abonado'?'active':'inactive'?>"><?=e($r['status'])?></span></td><td><?=$r['anexo_url']?'<a href="'.e($r['anexo_url']).'" target="_blank">Abrir</a>':'-'?></td></tr><?php endforeach?></tbody></table></div></div><?php else:?><div class="empty-state">Nenhum aviso de falta enviado.</div><?php endif ?>
<?php layout_footer();
