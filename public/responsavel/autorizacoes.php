<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_parent();
$service = \App\Support\ServiceFactory::withdrawalAuthorizations();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        if (($_POST['action'] ?? '') === 'cancelar') {
            $service->cancel((int)($_POST['id'] ?? 0), (int)$_SESSION['responsavel_id']);
            flash('Autorização cancelada.');
        } else {
            $service->create((int)$_SESSION['responsavel_id'], $_POST);
            flash('Autorização enviada para a portaria.');
        }
    } catch (Throwable $error) {
        flash($error->getMessage(), 'warning');
    }
    redirect('responsavel/autorizacoes.php');
}
$data = $service->guardianDashboard((int)$_SESSION['responsavel_id']);
$children = $data['children'];
$rows = $data['rows'];
layout_header('Autorizações de retirada');
?>
<div class="page-heading">
  <div><span class="gate-eyebrow">FAMÍLIA</span><h1>Autorizações de retirada</h1><p>Libere uma pessoa de confiança para retirar a criança por tempo limitado.</p></div>
</div>

<section class="section-card portal-form">
  <h2>Nova autorização temporária</h2>
  <form method="post" class="row g-3">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <div class="col-md-6"><label class="form-label fw-bold">Aluno</label><select class="form-select form-select-lg" name="aluno_id" required><option value="">Selecione</option><?php foreach($children as $child):?><option value="<?=(int)$child['id']?>"><?=e($child['nome'])?><?= $child['turma'] ? ' · '.e($child['turma']) : '' ?></option><?php endforeach?></select></div>
    <div class="col-md-6"><label class="form-label fw-bold">Válido até</label><input class="form-control form-control-lg" type="date" name="valido_ate" min="<?=e(date('Y-m-d'))?>" max="<?=e(date('Y-m-d', strtotime('+30 days')))?>" value="<?=e(date('Y-m-d'))?>" required></div>
    <div class="col-md-6"><label class="form-label fw-bold">Nome da pessoa autorizada</label><input class="form-control" name="nome_autorizado" maxlength="150" required></div>
    <div class="col-md-3"><label class="form-label fw-bold">Documento</label><input class="form-control" name="documento" maxlength="40"></div>
    <div class="col-md-3"><label class="form-label fw-bold">Telefone</label><input class="form-control" name="telefone" maxlength="30" inputmode="tel"></div>
    <div class="col-12"><label class="form-label fw-bold">Observação</label><input class="form-control" name="observacao" maxlength="255" placeholder="Ex.: retirar após 16h"></div>
    <div class="col-12"><button class="btn btn-primary btn-lg" type="submit">Enviar autorização</button></div>
  </form>
</section>

<?php if($rows): ?>
  <div class="data-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Aluno</th><th>Autorizado</th><th>Validade</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach($rows as $row): ?><tr>
    <td><?=e($row['aluno'])?><br><small><?=e($row['turma'] ?: '-')?></small></td>
    <td><strong><?=e($row['nome_autorizado'])?></strong><br><small><?=e(trim(($row['documento'] ?: '').' '.($row['telefone'] ?: '')) ?: '-')?></small></td>
    <td><?=e(date('d/m/Y', strtotime($row['valido_ate'])))?></td>
    <td><span class="status-pill <?=$row['status']==='ativa'?'active':'inactive'?>"><?=e($row['status'])?></span></td>
    <td class="text-end"><?php if($row['status']==='ativa'):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="cancelar"><input type="hidden" name="id" value="<?=(int)$row['id']?>"><button class="btn btn-sm btn-outline-secondary">Cancelar</button></form><?php endif?></td>
  </tr><?php endforeach ?>
  </tbody></table></div></div>
<?php else: ?>
  <div class="empty-state">Nenhuma autorização criada.</div>
<?php endif ?>
<?php layout_footer();
