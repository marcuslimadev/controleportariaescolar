<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_parent();
$absenceService = new \App\Services\AbsenceService(
    new \App\Infrastructure\Persistence\PdoAbsenceRepository(db()),
    new \App\Infrastructure\Logging\DatabaseAuditLogger(),
);
$children = $absenceService->childrenForGuardian((int)$_SESSION['responsavel_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $anexo = !empty($_FILES['anexo']) ? save_portal_upload($_FILES['anexo'], 'faltas') : null;
        $absenceService->createFromGuardian((int)$_SESSION['responsavel_id'], $_POST, $anexo);
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
