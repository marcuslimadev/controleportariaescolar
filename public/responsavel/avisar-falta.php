<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_parent();
$q = db()->prepare('SELECT a.id,a.nome,a.turma_id,t.nome turma FROM scp_aluno_responsavel ar JOIN scp_alunos a ON a.id=ar.aluno_id LEFT JOIN scp_turmas t ON t.id=a.turma_id WHERE ar.responsavel_id=? AND ar.autoriza_consulta=1 AND a.ativo=1 ORDER BY a.nome');
$q->execute([$_SESSION['responsavel_id']]);
$children = $q->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $alunoId = (int)($_POST['aluno_id'] ?? 0);
        $child = null;
        foreach ($children as $c) if ((int)$c['id'] === $alunoId) $child = $c;
        if (!$child) throw new RuntimeException('Aluno inválido.');
        $data = (string)($_POST['data_falta'] ?? '');
        if (!$data) throw new RuntimeException('Informe a data da falta.');
        $motivos = ['Doença','Consulta médica','Viagem','Compromisso familiar','Outro'];
        $motivo = in_array($_POST['motivo'] ?? '', $motivos, true) ? $_POST['motivo'] : 'Outro';
        $anexo = !empty($_FILES['anexo']) ? save_portal_upload($_FILES['anexo'], 'faltas') : null;
        $q = db()->prepare('INSERT INTO scp_avisos_falta(aluno_id,responsavel_id,turma_id,data_falta,motivo,observacao,anexo_url) VALUES(?,?,?,?,?,?,?)');
        $q->execute([$alunoId, $_SESSION['responsavel_id'], $child['turma_id'] ?: null, $data, $motivo, trim((string)($_POST['observacao'] ?? '')) ?: null, $anexo]);
        audit('enviar_aviso_falta', 'scp_avisos_falta', (int)db()->lastInsertId(), ['aluno_id'=>$alunoId]);
        flash('Aviso de falta enviado.');
        redirect('responsavel/minhas-faltas.php');
    } catch (Throwable $e) {
        flash('Não foi possível enviar: '.$e->getMessage(), 'danger');
    }
}
layout_header('Avisar falta');
?>
<div class="page-heading"><div><span class="gate-eyebrow">FREQUÊNCIA</span><h1>Avisar falta</h1><p>Informe a escola quando o aluno não puder comparecer.</p></div></div>
<form class="section-card portal-form" method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?=e(csrf())?>">
  <div class="row g-3">
    <div class="col-12"><label class="form-label fw-bold">Aluno</label><select class="form-select form-select-lg" name="aluno_id" required><option value="">Selecione</option><?php foreach($children as $c):?><option value="<?=$c['id']?>"><?=e($c['nome'])?><?= $c['turma'] ? ' - '.e($c['turma']) : '' ?></option><?php endforeach?></select></div>
    <div class="col-md-6"><label class="form-label fw-bold">Data da falta</label><input type="date" class="form-control form-control-lg" name="data_falta" value="<?=e(date('Y-m-d'))?>" required></div>
    <div class="col-md-6"><label class="form-label fw-bold">Motivo</label><select class="form-select form-select-lg" name="motivo"><?php foreach(['Doença','Consulta médica','Viagem','Compromisso familiar','Outro'] as $m):?><option><?=e($m)?></option><?php endforeach?></select></div>
    <div class="col-12"><label class="form-label fw-bold">Observação</label><textarea class="form-control" name="observacao" rows="4"></textarea></div>
    <div class="col-12"><label class="form-label fw-bold">Anexo</label><input type="file" class="form-control" name="anexo" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"></div>
  </div>
  <button class="btn-scan mt-4" type="submit">Enviar aviso</button>
</form>
<?php layout_footer();
