<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_role(['admin']);
$schoolService = \App\Support\ServiceFactory::schoolAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $schoolService->handleAction($_POST, (string)($_SESSION['role'] ?? ''));
        flash('Configurações salvas.');
    } catch (Throwable $error) {
        flash('Não foi possível salvar: ' . $error->getMessage(), 'danger');
    }
    redirect('admin/configuracoes.php');
}
$data = $schoolService->dashboardData();
$settings = $data['settings'] ?? [];
$currentTheme = $settings['tema'] ?? app_theme();
layout_header('Configurações');
?>
<div class="page-heading">
  <div>
    <span class="gate-eyebrow">ADMIN</span>
    <h1>Configurações</h1>
    <p>Tema visual e opções gerais do aplicativo.</p>
  </div>
</div>

<section class="section-card">
  <h2>Tema do sistema</h2>
  <p class="text-muted">O tema escolhido vale para todos os usuários, inclusive no PWA.</p>
  <form method="post" class="theme-picker-grid">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <input type="hidden" name="action" value="tema">
    <?php foreach(['classico'=>'Colorido atual','azul_branco'=>'Azul e branco moderno','preto_branco'=>'Preto e branco moderno'] as $value=>$label): ?>
      <label class="theme-choice theme-choice-<?=e($value)?> <?=$currentTheme===$value?'selected':''?>">
        <input type="radio" name="tema" value="<?=e($value)?>" <?=$currentTheme===$value?'checked':''?>>
        <span class="theme-swatch"><i></i><i></i><i></i></span>
        <strong><?=e($label)?></strong>
      </label>
    <?php endforeach ?>
    <button class="btn btn-primary btn-lg" type="submit">Salvar tema</button>
  </form>
</section>

<?php layout_footer();
