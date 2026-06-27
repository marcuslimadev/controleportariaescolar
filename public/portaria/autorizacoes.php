<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('access.read');
$service = \App\Support\ServiceFactory::withdrawalAuthorizations();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $service->markUsed((int)($_POST['id'] ?? 0), (int)$_SESSION['user_id']);
        flash('Autorização marcada como usada.');
    } catch (Throwable $error) {
        flash($error->getMessage(), 'warning');
    }
    redirect('portaria/autorizacoes.php');
}
$rows = $service->gateList();
layout_header('Autorizações de retirada');
?>
<div class="page-heading">
  <div><span class="gate-eyebrow">PORTARIA</span><h1>Autorizações de retirada</h1><p>Pessoas autorizadas temporariamente pelos responsáveis.</p></div>
  <div class="page-actions"><a class="btn btn-outline-primary" href="<?=e(url('portaria/index.php'))?>">Voltar ao leitor</a></div>
</div>

<?php if($rows): ?>
  <section class="authorization-grid">
    <?php foreach($rows as $row): ?>
      <article class="section-card authorization-card">
        <div class="section-title-row">
          <div>
            <h2><?=e($row['aluno'])?></h2>
            <p class="text-muted mb-0"><?=e($row['turma'] ?: 'Sem turma')?> · válido até <?=e(date('d/m/Y', strtotime($row['valido_ate'])))?></p>
          </div>
          <span class="status-pill active">Ativa</span>
        </div>
        <div class="authorization-person">
          <strong><?=e($row['nome_autorizado'])?></strong>
          <span>Documento: <?=e($row['documento'] ?: '-')?></span>
          <span>Telefone: <?=e($row['telefone'] ?: '-')?></span>
        </div>
        <p class="text-muted">Responsável: <?=e($row['responsavel'])?><?= $row['responsavel_telefone'] ? ' · '.e($row['responsavel_telefone']) : '' ?></p>
        <?php if($row['observacao']): ?><p class="authorization-note"><?=e($row['observacao'])?></p><?php endif ?>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=e(csrf())?>">
          <input type="hidden" name="id" value="<?=(int)$row['id']?>">
          <button class="btn btn-primary w-100" type="submit">Marcar como usada</button>
        </form>
      </article>
    <?php endforeach ?>
  </section>
<?php else: ?>
  <div class="empty-state">Nenhuma autorização ativa no momento.</div>
<?php endif ?>
<?php layout_footer();
