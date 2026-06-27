<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('post.manage');
$id = (int)($_GET['id'] ?? 0);
$postService = \App\Support\ServiceFactory::posts();
try {
    $data = $postService->scienceHistory($id, (int)$_SESSION['user_id'], (string)($_SESSION['role'] ?? ''));
} catch (Throwable $error) {
    flash($error->getMessage(), 'warning');
    redirect('admin/posts.php');
}
$post = $data['post'];
$rows = $data['rows'];
layout_header('Histórico de ciência');
?>
<div class="page-heading">
  <div>
    <span class="gate-eyebrow">COMUNICAÇÃO</span>
    <h1>Histórico de ciência</h1>
    <p><?=e($post['titulo'])?></p>
  </div>
  <div class="page-actions">
    <a class="btn btn-outline-primary" href="<?=e(url('admin/posts.php'))?>">Voltar</a>
    <a class="btn btn-primary" href="<?=e(url('admin/post-form.php?id='.(int)$post['id']))?>">Editar publicação</a>
  </div>
</div>

<section class="section-card">
  <div class="section-title-row">
    <div>
      <h2>Confirmações recebidas</h2>
      <p class="text-muted mb-0">Registro de usuários e responsáveis que confirmaram leitura.</p>
    </div>
    <span class="status-pill active"><?=(int)count($rows)?> ciência(s)</span>
  </div>
</section>

<?php if ($rows): ?>
  <div class="data-table-card">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr><th>Pessoa</th><th>Perfil</th><th>Confirmado em</th><th>IP</th><th>Dispositivo</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><strong><?=e($row['pessoa'] ?: 'Usuário removido')?></strong></td>
              <td><?=e($row['perfil'] ?: '-')?></td>
              <td><?=e(format_br_datetime($row['confirmado_em']))?></td>
              <td><?=e($row['ip'] ?: '-')?></td>
              <td><small><?=e($row['user_agent'] ?: '-')?></small></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
<?php else: ?>
  <div class="empty-state">Ainda não há confirmações de ciência para esta publicação.</div>
<?php endif ?>
<?php layout_footer();
