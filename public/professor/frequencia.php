<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('frequency.manage');
$date = $_GET['data'] ?? date('Y-m-d');
$turmaId = (int)($_GET['turma_id'] ?? 0);
$situacaoFiltro = (string)($_GET['situacao'] ?? '');
$nome = trim((string)($_GET['nome'] ?? ''));
$params = [];
$turmaWhere = 't.ativo=1';
if (($_SESSION['role'] ?? '') === 'professor') {
    $professorId = get_professor_id_for_user();
    $turmaWhere .= ' AND t.id IN (SELECT turma_id FROM scp_professor_turma WHERE professor_id=?)';
    $params[] = $professorId;
}
$turmaStmt = db()->prepare("SELECT t.id,t.nome FROM scp_turmas t WHERE $turmaWhere ORDER BY t.nome");
$turmaStmt->execute($params);
$turmas = $turmaStmt->fetchAll();
if (!$turmaId && $turmas) $turmaId = (int)$turmas[0]['id'];

$studentParams = [$date, $date, $date];
$where = 'a.ativo=1';
if ($turmaId) { $where .= ' AND a.turma_id=?'; $studentParams[] = $turmaId; }
if ($nome !== '') { $where .= ' AND a.nome LIKE ?'; $studentParams[] = '%'.$nome.'%'; }
if (($_SESSION['role'] ?? '') === 'professor') {
    $where .= ' AND a.turma_id IN (SELECT turma_id FROM scp_professor_turma WHERE professor_id=?)';
    $studentParams[] = get_professor_id_for_user();
}
$q = db()->prepare("SELECT a.id,a.nome,t.nome turma,
    (SELECT MAX(registrado_em) FROM scp_registros_acesso r WHERE r.aluno_id=a.id AND r.tipo='entrada' AND DATE(r.registrado_em)=?) ultima_entrada,
    (SELECT MAX(registrado_em) FROM scp_registros_acesso r WHERE r.aluno_id=a.id AND r.tipo='saida' AND DATE(r.registrado_em)=?) ultima_saida,
    (SELECT CONCAT(status,'|',motivo) FROM scp_avisos_falta af WHERE af.aluno_id=a.id AND af.data_falta=? ORDER BY af.id DESC LIMIT 1) aviso
    FROM scp_alunos a LEFT JOIN scp_turmas t ON t.id=a.turma_id WHERE $where ORDER BY a.nome");
$q->execute($studentParams);
$rows = [];
foreach ($q->fetchAll() as $r) {
    $situacao = 'sem registro';
    if ($r['ultima_entrada']) $situacao = 'presente';
    if ($r['ultima_saida'] && (!$r['ultima_entrada'] || strtotime($r['ultima_saida']) > strtotime($r['ultima_entrada']))) $situacao = 'saiu';
    if (!$r['ultima_entrada'] && !$r['ultima_saida']) $situacao = 'ausente';
    $avisoParts = $r['aviso'] ? explode('|', $r['aviso'], 2) : [];
    if ($avisoParts) $situacao = 'com aviso de falta';
    if ($situacaoFiltro !== '' && $situacao !== $situacaoFiltro) continue;
    $r['situacao'] = $situacao;
    $r['aviso_texto'] = $avisoParts ? (($avisoParts[0] ?? '') . ' - ' . ($avisoParts[1] ?? '')) : '';
    $rows[] = $r;
}
layout_header('Frequência');
?>
<div class="page-heading"><div><span class="gate-eyebrow">FREQUÊNCIA</span><h1>Frequência da turma</h1><p>Situação calculada por registros da portaria e avisos de falta.</p></div></div>
<form class="section-card row g-3 align-items-end"><div class="col-md-3"><label class="form-label fw-bold">Data</label><input type="date" class="form-control" name="data" value="<?=e($date)?>"></div><div class="col-md-3"><label class="form-label fw-bold">Turma</label><select class="form-select" name="turma_id"><?php foreach($turmas as $t):?><option value="<?=$t['id']?>" <?=$turmaId===(int)$t['id']?'selected':''?>><?=e($t['nome'])?></option><?php endforeach?></select></div><div class="col-md-3"><label class="form-label fw-bold">Situação</label><select class="form-select" name="situacao"><option value="">Todas</option><?php foreach(['presente','ausente','com aviso de falta','saiu','sem registro'] as $s):?><option value="<?=e($s)?>" <?=$situacaoFiltro===$s?'selected':''?>><?=e($s)?></option><?php endforeach?></select></div><div class="col-md-2"><label class="form-label fw-bold">Aluno</label><input class="form-control" name="nome" value="<?=e($nome)?>"></div><div class="col-auto"><button class="btn btn-primary">Filtrar</button></div></form>
<?php if($rows): ?><div class="data-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Aluno</th><th>Situação</th><th>Última entrada</th><th>Última saída</th><th>Aviso de falta</th><th>Observação</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['nome'])?><br><small><?=e($r['turma'] ?: '-')?></small></td><td><span class="status-pill <?=$r['situacao']==='presente'?'active':'inactive'?>"><?=e($r['situacao'])?></span></td><td><?=e(format_br_datetime($r['ultima_entrada']))?></td><td><?=e(format_br_datetime($r['ultima_saida']))?></td><td><?=e($r['aviso_texto'] ?: '-')?></td><td><?=e($r['situacao']==='sem registro' ? 'Sem movimentação no dia' : '')?></td></tr><?php endforeach?></tbody></table></div></div><?php else:?><div class="empty-state">Nenhum aluno encontrado para os filtros.</div><?php endif ?>
<?php layout_footer();
