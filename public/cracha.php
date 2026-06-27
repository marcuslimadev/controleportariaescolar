<?php
require __DIR__.'/../includes/bootstrap.php';
$qrToken=(string)($_GET['token']??'');
$inviteToken=(string)($_GET['convite']??'');
$responsavelIdParam=(int)($_GET['responsavel']??0);
$badgeService = \App\Support\ServiceFactory::badges();
$badge = $badgeService->publicGuardianBadge($qrToken ?: null, $inviteToken ?: null, $responsavelIdParam, !empty($_SESSION['responsavel_id']) ? (int)$_SESSION['responsavel_id'] : null);
if(!$badge){http_response_code(404);layout_header('Crachá não encontrado');echo '<div class="alert alert-warning mx-auto" style="max-width:520px">Crachá inválido ou ainda não aprovado.</div>';layout_footer();exit;}
$responsavel=$badge['guardian'];
$children=$badge['children'];
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
  <div class="badge-actions"><button class="btn-scan" id="print-badge" type="button">Imprimir ou salvar em PDF</button><a class="btn btn-outline-primary btn-lg" href="<?=e(url('login.php'))?>">Entrar no portal da família</a></div>
  <p class="privacy-note">Não compartilhe este link publicamente. Ele funciona como identificação digital.</p>
</section>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script><script nonce="<?=e(csp_nonce())?>">document.querySelector('#print-badge').addEventListener('click',()=>window.print());new QRCode(document.querySelector('#public-qrcode'),{text:<?=json_encode($responsavel['qr_token'])?>,width:250,height:250,correctLevel:QRCode.CorrectLevel.H});</script>
<?php layout_footer();
