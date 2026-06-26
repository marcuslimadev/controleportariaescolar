<?php
require __DIR__ . '/../includes/bootstrap.php';
$publicPosts = [];
try {
    $q = db()->query("SELECT p.tipo,p.titulo,p.conteudo,p.imagem_url,p.data_evento,p.hora_evento,p.local,p.importante,p.fixado,p.publicado_em,u.nome autor
        FROM scp_posts p
        JOIN scp_usuarios u ON u.id=p.autor_id
        WHERE p.status='publicado' AND p.publico='toda_escola'
        ORDER BY p.fixado DESC, p.publicado_em DESC, p.id DESC
        LIMIT 6");
    $publicPosts = $q->fetchAll();
} catch (Throwable $ignored) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $login=trim((string)($_POST['login']??''));
    $senha=(string)($_POST['senha']??'');
    $digits=preg_replace('/\D/','',$login);
    if (login_rate_blocked($login)) {
        $error='Muitas tentativas. Aguarde alguns minutos e tente novamente.';
    } else {

        $q=db()->prepare('SELECT * FROM scp_usuarios WHERE email=? AND ativo=1 LIMIT 1');
        $q->execute([strtolower($login)]);
        $user=$q->fetch();
        if($user&&password_verify($senha,$user['senha_hash'])){
            session_regenerate_id(true);
            $_SESSION=['user_id'=>(int)$user['id'],'role'=>$user['perfil'],'name'=>$user['nome'],'csrf'=>bin2hex(random_bytes(32))];
            login_rate_clear($login);
            audit('login_usuario');
            redirect($user['perfil']==='portaria'?'portaria/index.php':'feed.php');
        }

        if($digits!==''){
            $q=db()->prepare("SELECT * FROM scp_responsaveis WHERE ativo=1 AND (cpf=? OR REPLACE(REPLACE(REPLACE(REPLACE(telefone,' ',''),'-',''),'(',''),')','')=?) LIMIT 1");
            $q->execute([$digits,$digits]);
            $parent=$q->fetch();
            if($parent&&password_verify($senha,$parent['senha_hash'])){
                session_regenerate_id(true);
                $_SESSION=['responsavel_id'=>(int)$parent['id'],'name'=>$parent['nome'],'csrf'=>bin2hex(random_bytes(32))];
                login_rate_clear($login);
                audit('login_responsavel');
                redirect('feed.php');
            }
        }
        login_rate_hit($login);
        $error='Usuário ou senha inválidos.';
    }
}
layout_header('Entrar');
?>
<section class="login-shell">
  <div class="public-portal-card">
    <img class="public-portal-logo" src="<?=e(asset_url('assets/porta-aberta-logo.jpg'))?>" alt="<?=e(app_name())?>">
    <p class="public-portal-tagline"><?=e(app_tagline())?></p>
    <div class="public-portal-actions">
      <a href="#login-card" class="btn btn-primary">Entrar</a>
      <a href="<?=e(url('eventos.php'))?>" class="btn btn-outline-primary">Eventos</a>
    </div>
    <div class="public-feed">
      <h2>Portal público</h2>
      <?php if($publicPosts): ?>
        <?php foreach($publicPosts as $post): ?>
          <article class="public-post <?=$post['importante']?'important':''?>">
            <div class="feed-meta">
              <span class="post-type"><?=e($post['tipo'])?></span>
              <?php if($post['importante']): ?><span class="important-badge">Importante</span><?php endif ?>
            </div>
            <h3><?=e($post['titulo'])?></h3>
            <p><?=nl2br(e(portal_excerpt($post['conteudo'], 190)))?></p>
            <?php if($post['imagem_url']): ?><img src="<?=e($post['imagem_url'])?>" alt="Imagem da publicação"><?php endif ?>
            <?php if($post['data_evento']): ?><div class="event-strip"><strong><?=e(date('d/m/Y', strtotime($post['data_evento'])))?></strong><?php if($post['hora_evento']): ?> às <?=e(substr($post['hora_evento'],0,5))?><?php endif ?><?php if($post['local']): ?> · <?=e($post['local'])?><?php endif ?></div><?php endif ?>
            <small><?=e(format_br_datetime($post['publicado_em']))?></small>
          </article>
        <?php endforeach ?>
      <?php else: ?>
        <div class="empty-state">Nenhuma publicação pública no momento.</div>
      <?php endif ?>
    </div>
  </div>
  <div id="login-card" class="login-card card">
    <div class="card-body">
      <div class="login-mobile-brand official"><img src="<?=e(asset_url('assets/porta-aberta-logo.jpg'))?>" alt="<?=e(app_name())?>"></div>
      <span class="gate-eyebrow">BEM-VINDO</span>
      <h2>Acesse sua conta</h2>
      <p class="login-hint"><?=e(app_tagline())?></p>
      <?php if(isset($error)):?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?=e(csrf())?>">
        <label class="form-label fw-bold" for="login">Usuário, CPF ou telefone</label>
        <input id="login" class="form-control form-control-lg" name="login" value="<?=e($_POST['login']??'')?>" required autocomplete="username" inputmode="text" placeholder="Digite seu acesso" autofocus>
        <label class="form-label fw-bold mt-3" for="senha">Senha</label>
        <div class="password-field"><input id="senha" class="form-control form-control-lg" type="password" name="senha" required autocomplete="current-password" placeholder="Digite sua senha"><button id="toggle-password" type="button" aria-label="Mostrar senha">Mostrar</button></div>
        <button class="btn-scan mt-4" type="submit">Entrar</button>
      </form>
    </div>
  </div>
</section>
<script>const password=document.querySelector('#senha'),toggle=document.querySelector('#toggle-password');toggle.addEventListener('click',()=>{const show=password.type==='password';password.type=show?'text':'password';toggle.textContent=show?'Ocultar':'Mostrar';toggle.setAttribute('aria-label',show?'Ocultar senha':'Mostrar senha')});</script>
<?php layout_footer();
