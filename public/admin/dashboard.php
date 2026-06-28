<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_role(['admin', 'secretaria']);

$reportService = \App\Support\ServiceFactory::reports();
$date = (string)($_GET['data'] ?? date('Y-m-d'));
$dashboard = $reportService->dashboard($date);

layout_header('Dashboard');
?>
<div class="page-heading">
  <div>
    <span class="gate-eyebrow">GESTÃO</span>
    <h1>Dashboard</h1>
    <p>Visão rápida da portaria e da comunicação escolar.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-primary" href="<?=e(url('portaria/index.php'))?>">Abrir portaria</a>
    <a class="btn btn-outline-primary" href="<?=e(url('admin/relatorios.php?de=' . rawurlencode($dashboard['data']) . '&ate=' . rawurlencode($dashboard['data'])))?>">Ver relatório</a>
  </div>
</div>

<form class="section-card dashboard-filter">
  <label class="form-label fw-bold" for="data">Data da portaria</label>
  <div class="input-group input-group-lg">
    <input id="data" class="form-control" type="date" name="data" value="<?=e($dashboard['data'])?>">
    <button class="btn btn-primary">Atualizar</button>
  </div>
</form>

<section class="dashboard-grid">
  <article class="dashboard-card primary">
    <span>Movimentações hoje</span>
    <strong><?=e((string)$dashboard['acessos_total'])?></strong>
    <small><?=e((string)$dashboard['alunos_movimentados'])?> aluno(s) envolvidos</small>
  </article>
  <article class="dashboard-card">
    <span>Entradas</span>
    <strong><?=e((string)$dashboard['entradas'])?></strong>
    <small>Registros de chegada</small>
  </article>
  <article class="dashboard-card">
    <span>Saídas</span>
    <strong><?=e((string)$dashboard['saidas'])?></strong>
    <small>Registros de retirada</small>
  </article>
  <article class="dashboard-card warn">
    <span>Cadastros pendentes</span>
    <strong><?=e((string)$dashboard['cadastros_pendentes'])?></strong>
    <small><a href="<?=e(url('portaria/convites.php'))?>">Revisar convites</a></small>
  </article>
</section>

<section class="dashboard-grid secondary">
  <article class="dashboard-card">
    <span>Publicações 7 dias</span>
    <strong><?=e((string)$dashboard['posts_7d'])?></strong>
    <small><?=e((string)$dashboard['posts_publicos_7d'])?> públicas · <?=e((string)$dashboard['posts_privados_7d'])?> internas</small>
  </article>
  <article class="dashboard-card warn">
    <span>Comentários pendentes</span>
    <strong><?=e((string)$dashboard['comentarios_pendentes'])?></strong>
    <small><a href="<?=e(url('admin/comentarios.php'))?>">Moderar comentários</a></small>
  </article>
  <article class="dashboard-card warn">
    <span>Avisos de falta</span>
    <strong><?=e((string)$dashboard['avisos_falta_pendentes'])?></strong>
    <small><a href="<?=e(url('admin/avisos-falta.php'))?>">Analisar solicitações</a></small>
  </article>
</section>
<?php layout_footer();
