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
$schoolName = $settings['nome_escola'] ?? app_name();
$schoolTagline = $settings['texto_institucional'] ?? app_tagline();
$primaryColor = $settings['cor_principal'] ?? app_primary_color();
$logoUrl = $settings['logo_url'] ?? '';
$coverUrl = $settings['capa_url'] ?? '';
layout_header('Configurações');
?>
<div class="page-heading">
  <div>
    <span class="gate-eyebrow">ADMIN</span>
    <h1>Configurações</h1>
    <p>Identidade visual, tema padrão e opções gerais do aplicativo.</p>
  </div>
</div>

<section class="section-card identity-settings-card">
  <div class="section-title-row">
    <div>
      <h2>Identidade visual</h2>
      <p class="text-muted mb-0">Essas informações aparecem no PWA, no menu, no login e no feed público.</p>
    </div>
    <span class="status-pill active">Global</span>
  </div>
  <form method="post" class="row g-3 align-items-start">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <input type="hidden" name="action" value="identidade">
    <div class="col-lg-7">
      <label class="form-label fw-bold" for="nome_escola">Nome da escola</label>
      <input id="nome_escola" class="form-control form-control-lg" name="nome_escola" maxlength="80" value="<?=e($schoolName)?>" required>
      <label class="form-label fw-bold mt-3" for="texto_institucional">Texto institucional</label>
      <textarea id="texto_institucional" class="form-control" name="texto_institucional" rows="3" maxlength="180"><?=e($schoolTagline)?></textarea>
      <div class="row g-3 mt-1">
        <div class="col-sm-6">
          <label class="form-label fw-bold" for="cor_principal">Cor principal</label>
          <input id="cor_principal" class="form-control form-control-color identity-color-input" type="color" name="cor_principal" value="<?=e(preg_match('/^#[0-9A-Fa-f]{6}$/', (string)$primaryColor) ? $primaryColor : '#1356A2')?>">
        </div>
        <div class="col-sm-6">
          <label class="form-label fw-bold" for="logo_url">Logo</label>
          <input id="logo_url" class="form-control" name="logo_url" placeholder="assets/logo.png ou https://..." value="<?=e($logoUrl)?>">
        </div>
      </div>
      <label class="form-label fw-bold mt-3" for="capa_url">Imagem de capa da página pública</label>
      <input id="capa_url" class="form-control" name="capa_url" placeholder="uploads/capas/escola.webp ou https://..." value="<?=e($coverUrl)?>">
      <button class="btn btn-primary btn-lg mt-4" type="submit">Salvar identidade</button>
    </div>
    <div class="col-lg-5">
      <article class="identity-preview">
        <?php if (app_cover_url()): ?><img class="identity-preview-cover" src="<?=e(app_cover_url())?>" alt="Capa atual"><?php endif ?>
        <div class="identity-preview-brand">
          <img src="<?=e(app_logo_url())?>" alt="<?=e(app_name())?>">
          <div>
            <strong><?=e($schoolName)?></strong>
            <span><?=e($schoolTagline)?></span>
          </div>
        </div>
        <div class="identity-preview-actions">
          <span class="identity-preview-dot"></span>
          <p>Prévia do cabeçalho público e do app instalado.</p>
        </div>
      </article>
    </div>
  </form>
</section>

<section class="section-card">
  <h2>Tema do sistema</h2>
  <p class="text-muted">O tema escolhido vale para todos os usuários, inclusive no PWA.</p>
  <form method="post" class="theme-picker-grid">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <input type="hidden" name="action" value="tema">
    <?php foreach(['classico'=>'Colorido escolar','azul_branco'=>'Azul corporativo','preto_branco'=>'Preto e branco editorial'] as $value=>$label): ?>
      <label class="theme-choice theme-choice-<?=e($value)?> <?=$currentTheme===$value?'selected':''?>">
        <input type="radio" name="tema" value="<?=e($value)?>" <?=$currentTheme===$value?'checked':''?>>
        <span class="theme-swatch"><i></i><i></i><i></i></span>
        <strong><?=e($label)?></strong>
        <small class="theme-selected-label">Selecionado</small>
      </label>
    <?php endforeach ?>
    <button class="btn btn-primary btn-lg" type="submit">Salvar tema</button>
  </form>
</section>

<?php layout_footer();
