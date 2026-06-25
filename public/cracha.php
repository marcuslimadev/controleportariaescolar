<?php
require __DIR__.'/../includes/bootstrap.php';
$qrToken=(string)($_GET['token']??'');
$inviteToken=(string)($_GET['convite']??'');
$responsavelIdParam=(int)($_GET['responsavel']??0);
$responsavel=null;

if($qrToken!==''){
    $q=db()->prepare('SELECT * FROM scp_responsaveis WHERE qr_token=? AND ativo=1');
    $q->execute([$qrToken]);
    $responsavel=$q->fetch();
}
if(!$responsavel && $inviteToken!==''&&$responsavelIdParam){
    $q=db()->prepare("SELECT r.* FROM scp_convites_cadastro c JOIN scp_responsaveis r ON r.id=c.responsavel_id WHERE c.token_hash=? AND c.responsavel_id=? AND c.status='aprovado'");
    $q->execute([hash('sha256',$inviteToken),$responsavelIdParam]);
    $responsavel=$q->fetch();
}
if(!$responsavel && !empty($_SESSION['responsavel_id'])){
    $q=db()->prepare('SELECT * FROM scp_responsaveis WHERE id=? AND ativo=1');
    $q->execute([$_SESSION['responsavel_id']]);
    $responsavel=$q->fetch();
}
if($responsavel && !$responsavel['qr_token']){
    $responsavel['qr_token']=bin2hex(random_bytes(32));
    db()->prepare('UPDATE scp_responsaveis SET qr_token=? WHERE id=?')->execute([$responsavel['qr_token'],$responsavel['id']]);
}
if(!$responsavel){http_response_code(404);layout_header('Crachá não encontrado');echo '<div class="alert alert-warning mx-auto" style="max-width:520px">Crachá inválido ou ainda não aprovado.</div>';layout_footer();exit;}

$q=db()->prepare('SELECT a.nome,a.foto,t.nome turma FROM scp_alunos a JOIN scp_aluno_responsavel ar ON ar.aluno_id=a.id LEFT JOIN scp_turmas t ON t.id=a.turma_id WHERE ar.responsavel_id=? AND ar.autoriza_retirada=1 AND a.ativo=1 ORDER BY a.nome');
$q->execute([$responsavel['id']]);
$children=$q->fetchAll();
layout_header('Crachá digital');
?>
<section class="public-badge">
  <div class="text-center mb-3"><span class="gate-eyebrow">CRACHÁ DIGITAL</span><h1><?=e($responsavel['nome'])?></h1><p>Apresente esta tela na entrada e na saída.</p></div>
  <article class="mobile-credential">
    <?php if($responsavel['foto']):?><img class="credential-photo" src="<?=e($responsavel['foto'])?>" alt="Foto de <?=e($responsavel['nome'])?>"><?php endif?>
    <h2><?=e($responsavel['nome'])?></h2>
    <span>Responsável autorizado</span>
    <div id="public-qrcode"></div>
    <strong><?=e(app_name())?></strong>
  </article>
  <?php if($children):?>
  <div class="family-cards mt-3">
    <?php foreach($children as $c):?><article><?php if($c['foto']):?><img src="<?=e($c['foto'])?>" alt="Foto de <?=e($c['nome'])?>"><?php endif?><div><strong><?=e($c['nome'])?></strong><span><?=e($c['turma']??'Sem turma')?></span></div></article><?php endforeach?>
  </div>
  <?php endif?>
  <div class="badge-actions"><button class="btn-scan" type="button" onclick="window.print()">Imprimir ou salvar em PDF</button><a class="btn btn-outline-primary btn-lg" href="<?=e(url('login.php'))?>">Entrar no portal da família</a></div>
  <p class="privacy-note">Não compartilhe este link publicamente. Ele funciona como identificação digital.</p>
</section>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script><script>new QRCode(document.querySelector('#public-qrcode'),{text:<?=json_encode($responsavel['qr_token'])?>,width:250,height:250,correctLevel:QRCode.CorrectLevel.H});</script>
<?php layout_footer();
