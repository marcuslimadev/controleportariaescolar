<?php
require __DIR__ . '/../includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $type = $_POST['tipo'] ?? 'equipe'; $login = trim((string)($_POST['login'] ?? '')); $senha = (string)($_POST['senha'] ?? '');
    if ($type === 'responsavel') {
        $q=db()->prepare('SELECT * FROM scp_responsaveis WHERE cpf=? AND ativo=1'); $q->execute([preg_replace('/\D/', '', $login)]); $u=$q->fetch();
        if ($u && password_verify($senha, $u['senha_hash'])) { session_regenerate_id(true); $_SESSION=['responsavel_id'=>(int)$u['id'],'name'=>$u['nome'],'csrf'=>bin2hex(random_bytes(32))]; audit('login_responsavel'); redirect('responsavel/index.php'); }
    } else {
        $q=db()->prepare('SELECT * FROM scp_usuarios WHERE email=? AND ativo=1'); $q->execute([strtolower($login)]); $u=$q->fetch();
        if ($u && password_verify($senha, $u['senha_hash'])) { session_regenerate_id(true); $_SESSION=['user_id'=>(int)$u['id'],'role'=>$u['perfil'],'name'=>$u['nome'],'csrf'=>bin2hex(random_bytes(32))]; audit('login_usuario'); redirect($u['perfil']==='admin'?'admin/index.php':'portaria/index.php'); }
    }
    $error='Credenciais inválidas.';
}
layout_header('Entrar');
?>
<div class="login-card card shadow-sm mx-auto"><div class="card-body p-4"><h1 class="h3 mb-4">Acesso seguro</h1><?php if(isset($error)):?><div class="alert alert-danger"><?=e($error)?></div><?php endif?>
<form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><label class="form-label">Área</label><select class="form-select mb-3" name="tipo"><option value="equipe">Escola / Portaria</option><option value="responsavel">Pais e responsáveis</option></select><label class="form-label">E-mail ou CPF</label><input class="form-control mb-3" name="login" required autocomplete="username"><label class="form-label">Senha</label><input class="form-control mb-4" type="password" name="senha" required autocomplete="current-password"><button class="btn btn-primary w-100 btn-lg">Entrar</button></form></div></div>
<?php layout_footer();

