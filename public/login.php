<?php
require __DIR__ . '/../includes/bootstrap.php';
$publicPosts = [];
try {
    $postService = \App\Support\ServiceFactory::posts();
    $publicPosts = $postService->publicPosts(6);
} catch (Throwable $ignored) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $login=trim((string)($_POST['login']??''));
    $senha=(string)($_POST['senha']??'');
    if (login_rate_blocked($login)) {
        $error=t('too_many_attempts');
    } else {
        $authService = \App\Support\ServiceFactory::auth();
        $actor = $authService->authenticate($login, $senha);
        if($actor){
            session_regenerate_id(true);
            if($actor['type']==='user') $_SESSION=['user_id'=>$actor['id'],'role'=>$actor['role'],'name'=>$actor['name'],'csrf'=>bin2hex(random_bytes(32))];
            else $_SESSION=['responsavel_id'=>$actor['id'],'name'=>$actor['name'],'csrf'=>bin2hex(random_bytes(32))];
            login_rate_clear($login);
            audit($actor['audit']);
            redirect($actor['home']);
        }
        login_rate_hit($login);
        $error=t('invalid_login');
    }
}
layout_header('Entrar');
?>
<section class="login-shell">
  <div class="public-portal-card">
    <img class="public-portal-logo" src="<?=e(asset_url('assets/porta-aberta-logo.jpg'))?>" alt="<?=e(app_name())?>">
    <p class="public-portal-tagline"><?=e(app_tagline())?></p>
    <div class="public-portal-actions">
      <a href="#login-card" class="btn btn-primary"><?=e(t('login'))?></a>
      <a href="<?=e(url('eventos.php'))?>" class="btn btn-outline-primary"><?=e(t('events'))?></a>
      <a href="<?=e(lang_url(current_locale()==='en'?'pt':'en'))?>" class="btn btn-outline-secondary"><?=e(current_locale()==='en'?'Português':'English')?></a>
    </div>
    <div class="public-feed">
      <h2><?=e(t('public_portal'))?></h2>
      <?php if($publicPosts): ?>
        <?php foreach($publicPosts as $post): ?>
          <article class="public-post <?=$post['importante']?'important':''?>">
            <div class="feed-meta">
              <span class="post-type"><?=e($post['tipo'])?></span>
              <?php if($post['importante']): ?><span class="important-badge"><?=e(t('important'))?></span><?php endif ?>
            </div>
            <h3><?=e($post['titulo'])?></h3>
            <p><?=nl2br(e(portal_excerpt($post['conteudo'], 190)))?></p>
            <?php if($post['imagem_url']): ?><img src="<?=e($post['imagem_url'])?>" alt="<?=e(current_locale()==='en'?'Post image':'Imagem da publicação')?>"><?php endif ?>
            <?php if($post['data_evento']): ?><div class="event-strip"><strong><?=e(date('d/m/Y', strtotime($post['data_evento'])))?></strong><?php if($post['hora_evento']): ?> às <?=e(substr($post['hora_evento'],0,5))?><?php endif ?><?php if($post['local']): ?> · <?=e($post['local'])?><?php endif ?></div><?php endif ?>
            <small><?=e(format_br_datetime($post['publicado_em']))?></small>
          </article>
        <?php endforeach ?>
      <?php else: ?>
        <div class="empty-state"><?=e(t('no_public_posts'))?></div>
      <?php endif ?>
    </div>
  </div>
  <div id="login-card" class="login-card card">
    <div class="card-body">
      <div class="login-mobile-brand official"><img src="<?=e(asset_url('assets/porta-aberta-logo.jpg'))?>" alt="<?=e(app_name())?>"></div>
      <span class="gate-eyebrow"><?=e(t('welcome'))?></span>
      <h2><?=e(t('access_account'))?></h2>
      <p class="login-hint"><?=e(app_tagline())?></p>
      <?php if(isset($error)):?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?=e(csrf())?>">
        <label class="form-label fw-bold" for="login"><?=e(t('login_identifier'))?></label>
        <input id="login" class="form-control form-control-lg" name="login" value="<?=e($_POST['login']??'')?>" required autocomplete="username" inputmode="text" placeholder="<?=e(t('login_placeholder'))?>" autofocus>
        <label class="form-label fw-bold mt-3" for="senha"><?=e(t('password'))?></label>
        <div class="password-field"><input id="senha" class="form-control form-control-lg" type="password" name="senha" required autocomplete="current-password" placeholder="<?=e(t('password_placeholder'))?>"><button id="toggle-password" type="button" aria-label="<?=e(t('show_password'))?>"><?=e(t('show_password'))?></button></div>
        <button class="btn-scan mt-4" type="submit"><?=e(t('login'))?></button>
      </form>
    </div>
  </div>
</section>
<script nonce="<?=e(csp_nonce())?>">const password=document.querySelector('#senha'),toggle=document.querySelector('#toggle-password'),showLabel=<?=json_encode(t('show_password'))?>,hideLabel=<?=json_encode(t('hide_password'))?>;toggle.addEventListener('click',()=>{const show=password.type==='password';password.type=show?'text':'password';toggle.textContent=show?hideLabel:showLabel;toggle.setAttribute('aria-label',show?hideLabel:showLabel)});</script>
<?php layout_footer();
