<?php
require __DIR__ . '/../includes/bootstrap.php';
$publicPosts = [];
try {
    $postService = \App\Support\ServiceFactory::posts();
    $publicPosts = $postService->publicPosts(12);
} catch (Throwable $ignored) {}

$showLogin = isset($_GET['login']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $showLogin = true;
    verify_csrf();
    $login = trim((string)($_POST['login'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');
    if (login_rate_blocked($login)) {
        $error = t('too_many_attempts');
    } else {
        $authService = \App\Support\ServiceFactory::auth();
        $actor = $authService->authenticate($login, $senha);
        if ($actor) {
            session_regenerate_id(true);
            if ($actor['type'] === 'user') {
                $_SESSION = ['user_id' => $actor['id'], 'role' => $actor['role'], 'name' => $actor['name'], 'csrf' => bin2hex(random_bytes(32))];
            } else {
                $_SESSION = ['responsavel_id' => $actor['id'], 'name' => $actor['name'], 'csrf' => bin2hex(random_bytes(32))];
            }
            login_rate_clear($login);
            audit($actor['audit']);
            redirect($actor['home']);
        }
        login_rate_hit($login);
        $error = t('invalid_login');
    }
}
layout_header(t('public_portal'));
$nextLang = current_locale() === 'en' ? 'pt' : 'en';
$coverUrl = app_cover_url();
?>
<section class="public-home <?=$showLogin ? 'show-login' : ''?>">
  <header class="public-topbar">
    <a class="public-brand" href="<?=e(url('login.php'))?>">
      <img src="<?=e(app_logo_url())?>" alt="<?=e(app_name())?>">
      <span><strong><?=e(app_name())?></strong><small><?=e(app_tagline())?></small></span>
    </a>
    <nav class="public-top-actions" aria-label="Ações iniciais">
      <a href="<?=e(url('login.php?login=1#login-card'))?>" class="btn btn-primary"><?=e(t('login'))?></a>
      <a href="<?=e(url('eventos.php'))?>" class="btn btn-outline-primary"><?=e(t('events'))?></a>
      <a href="<?=e(lang_url($nextLang))?>" class="btn btn-outline-secondary lang-button login-lang-button" aria-label="<?=e($nextLang === 'pt' ? 'Trocar idioma para Português' : 'Change language to English')?>">
        <span class="lang-flag" aria-hidden="true"><?=$nextLang === 'pt' ? '🇧🇷' : '🇺🇸'?></span>
        <span class="lang-code"><?=e(strtoupper($nextLang))?></span>
      </a>
    </nav>
  </header>

  <?php if ($coverUrl): ?>
    <figure class="public-cover-card">
      <img src="<?=e($coverUrl)?>" alt="<?=e(app_name())?>">
      <figcaption><?=e(app_tagline())?></figcaption>
    </figure>
  <?php endif ?>

  <div id="login-card" class="login-card card">
    <div class="card-body">
      <div class="login-mobile-brand official"><img src="<?=e(app_logo_url())?>" alt="<?=e(app_name())?>"></div>
      <span class="gate-eyebrow"><?=e(t('welcome'))?></span>
      <h2><?=e(t('access_account'))?></h2>
      <p class="login-hint"><?=e(app_tagline())?></p>
      <?php if (isset($error)): ?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?=e(csrf())?>">
        <label class="form-label fw-bold" for="login"><?=e(t('login_identifier'))?></label>
        <input id="login" class="form-control form-control-lg" name="login" value="<?=e($_POST['login'] ?? '')?>" required autocomplete="username" inputmode="text" placeholder="<?=e(t('login_placeholder'))?>" <?=$showLogin ? 'autofocus' : ''?>>
        <label class="form-label fw-bold mt-3" for="senha"><?=e(t('password'))?></label>
        <div class="password-field"><input id="senha" class="form-control form-control-lg" type="password" name="senha" required autocomplete="current-password" placeholder="<?=e(t('password_placeholder'))?>"><button id="toggle-password" type="button" aria-label="<?=e(t('show_password'))?>"><?=e(t('show_password'))?></button></div>
        <button class="btn-scan mt-4" type="submit"><?=e(t('login'))?></button>
      </form>
    </div>
  </div>

  <section class="public-feed-shell" aria-label="<?=e(t('public_portal'))?>">
    <div class="public-feed-title">
      <span class="gate-eyebrow"><?=e(t('official_portal'))?></span>
      <h1><?=e(t('timeline'))?></h1>
    </div>
    <?php if ($publicPosts): ?>
      <div class="public-instagram-feed">
        <?php foreach ($publicPosts as $post): ?>
          <article class="public-insta-post <?=$post['importante'] ? 'important' : ''?>">
            <div class="public-post-head">
              <img src="<?=e(app_logo_url())?>" alt="">
              <div><strong><?=e(app_name())?></strong><span><?=e(format_br_datetime($post['publicado_em']))?></span></div>
              <?php if ($post['importante']): ?><em><?=e(t('important'))?></em><?php endif ?>
            </div>
            <?php if ($post['imagem_url']): ?>
              <img class="public-post-image" src="<?=e($post['imagem_url'])?>" alt="<?=e(current_locale() === 'en' ? 'Post image' : 'Imagem da publicação')?>">
            <?php endif ?>
            <div class="public-post-body">
              <div class="feed-meta"><span class="post-type"><?=e($post['tipo'])?></span></div>
              <h2><?=e($post['titulo'])?></h2>
              <p><?=nl2br(e(portal_excerpt($post['conteudo'], 260)))?></p>
              <?php if ($post['data_evento']): ?><div class="event-strip"><strong><?=e(date('d/m/Y', strtotime($post['data_evento'])))?></strong><?php if ($post['hora_evento']): ?> às <?=e(substr($post['hora_evento'], 0, 5))?><?php endif ?><?php if ($post['local']): ?> · <?=e($post['local'])?><?php endif ?></div><?php endif ?>
              <?php if (!empty($post['anexo_url'])): ?><a class="attachment-card" href="<?=e($post['anexo_url'])?>" target="_blank" rel="noopener"><span>📎</span><strong><?=e($post['anexo_nome'] ?: 'Abrir anexo')?></strong></a><?php endif ?>
            </div>
          </article>
        <?php endforeach ?>
      </div>
    <?php else: ?>
      <div class="empty-state"><?=e(t('no_public_posts'))?></div>
    <?php endif ?>
  </section>
</section>
<script nonce="<?=e(csp_nonce())?>">const password=document.querySelector('#senha'),toggle=document.querySelector('#toggle-password'),showLabel=<?=json_encode(t('show_password'))?>,hideLabel=<?=json_encode(t('hide_password'))?>;toggle.addEventListener('click',()=>{const show=password.type==='password';password.type=show?'text':'password';toggle.textContent=show?hideLabel:showLabel;toggle.setAttribute('aria-label',show?hideLabel:showLabel)});</script>
<?php layout_footer();
