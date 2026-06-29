<?php
require __DIR__ . '/../includes/bootstrap.php';
$filters = [
    'q' => (string)($_GET['q'] ?? ''),
    'tipo' => (string)($_GET['tipo'] ?? ''),
];
$items = [];
try {
    $items = \App\Support\ServiceFactory::posts()->publicGallery(80, $filters);
} catch (Throwable $ignored) {}
layout_header(current_locale()==='en' ? 'Gallery' : 'Galeria');
$nextLang = current_locale() === 'en' ? 'pt' : 'en';
?>
<section class="public-home public-gallery-page">
  <header class="public-topbar">
    <a class="public-brand" href="<?=e(url('login.php'))?>">
      <img src="<?=e(app_logo_url())?>" alt="<?=e(app_name())?>">
      <span><strong><?=e(app_name())?></strong><small><?=e(app_tagline())?></small></span>
    </a>
    <nav class="public-top-actions" aria-label="Ações públicas">
      <a href="<?=e(url('login.php'))?>" class="btn btn-outline-primary"><?=e(current_locale()==='en' ? 'Timeline' : 'Timeline')?></a>
      <a href="<?=e(url('eventos.php'))?>" class="btn btn-outline-primary"><?=e(t('events'))?></a>
      <a href="<?=e(lang_url($nextLang))?>" class="btn btn-outline-secondary lang-button">
        <span class="lang-flag" aria-hidden="true"><?=$nextLang === 'pt' ? '🇧🇷' : '🇺🇸'?></span>
        <span class="lang-code"><?=e(strtoupper($nextLang))?></span>
      </a>
    </nav>
  </header>

  <div class="public-feed-title">
    <span class="gate-eyebrow"><?=e(current_locale()==='en' ? 'PUBLIC GALLERY' : 'GALERIA PÚBLICA')?></span>
    <h1><?=e(current_locale()==='en' ? 'School moments' : 'Momentos da escola')?></h1>
  </div>

  <form class="public-gallery-filter">
    <input type="hidden" name="lang" value="<?=e(current_locale())?>">
    <input class="form-control" name="q" value="<?=e($filters['q'])?>" placeholder="<?=e(current_locale()==='en' ? 'Search public moments' : 'Buscar momentos públicos')?>">
    <select class="form-select" name="tipo">
      <option value=""><?=e(current_locale()==='en' ? 'All types' : 'Todos os tipos')?></option>
      <?php foreach(['comunicado','atividade','evento','programação','alerta','cardápio','lembrete'] as $type): ?><option value="<?=e($type)?>" <?=$filters['tipo']===$type?'selected':''?>><?=e(ucfirst($type))?></option><?php endforeach ?>
    </select>
    <button class="btn btn-primary"><?=e(current_locale()==='en' ? 'Filter' : 'Filtrar')?></button>
    <a class="btn btn-outline-primary" href="<?=e(url('galeria.php'))?>"><?=e(current_locale()==='en' ? 'Clear' : 'Limpar')?></a>
  </form>

  <?php if ($items): ?>
    <section class="public-gallery-grid">
      <?php foreach ($items as $item): ?>
        <article class="public-gallery-card">
          <img src="<?=e(media_url($item['imagem_url'], $item['publicado_em'] ?? ''))?>" alt="<?=e($item['titulo'])?>">
          <div>
            <span><?=e($item['tipo'])?> · <?=e(format_br_datetime($item['publicado_em']))?></span>
            <h2><?=e($item['titulo'])?></h2>
            <p><?=e(portal_excerpt($item['conteudo'], 140))?></p>
          </div>
        </article>
      <?php endforeach ?>
    </section>
  <?php else: ?>
    <div class="empty-state"><?=e(current_locale()==='en' ? 'No public images found.' : 'Nenhuma imagem pública encontrada.')?></div>
  <?php endif ?>
</section>
<?php layout_footer();
