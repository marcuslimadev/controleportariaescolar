<?php
require __DIR__ . '/../includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $login=trim((string)($_POST['login']??''));
    $senha=(string)($_POST['senha']??'');
    $digits=preg_replace('/\D/','',$login);

    $q=db()->prepare('SELECT * FROM scp_usuarios WHERE email=? AND ativo=1 LIMIT 1');
    $q->execute([strtolower($login)]);
    $user=$q->fetch();
    if($user&&password_verify($senha,$user['senha_hash'])){
        session_regenerate_id(true);
        $_SESSION=['user_id'=>(int)$user['id'],'role'=>$user['perfil'],'name'=>$user['nome'],'csrf'=>bin2hex(random_bytes(32))];
        audit('login_usuario');
        redirect($user['perfil']==='admin'?'admin/index.php':'portaria/index.php');
    }

    if($digits!==''){
        $q=db()->prepare("SELECT * FROM scp_responsaveis WHERE ativo=1 AND (cpf=? OR REPLACE(REPLACE(REPLACE(REPLACE(telefone,' ',''),'-',''),'(',''),')','')=?) LIMIT 1");
        $q->execute([$digits,$digits]);
        $parent=$q->fetch();
        if($parent&&password_verify($senha,$parent['senha_hash'])){
            session_regenerate_id(true);
            $_SESSION=['responsavel_id'=>(int)$parent['id'],'name'=>$parent['nome'],'csrf'=>bin2hex(random_bytes(32))];
            audit('login_responsavel');
            redirect('responsavel/index.php');
        }
    }
    $error='Usuário ou senha inválidos.';
}
layout_header('Entrar');
?>
<section class="login-shell">
  <div class="login-card card">
    <div class="card-body">
      <div class="login-mobile-brand"><span>SCP</span><strong>Controle Escolar</strong></div>
      <span class="gate-eyebrow">BEM-VINDO</span>
      <h2>Acesse sua conta</h2>
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
