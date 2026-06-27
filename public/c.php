<?php
require __DIR__.'/../includes/bootstrap.php';
$emergencyService = \App\Support\ServiceFactory::emergencyBadges();
$token=extract_qr_token((string)($_GET['token']??''));
$student=$emergencyService->findPublicBadge($token);
if(!$student){http_response_code(404);layout_header('Crachá não encontrado');echo '<section class="emergency-page"><div class="emergency-card"><span class="emergency-icon">!</span><h1>Crachá inválido</h1><p>Este crachá não foi encontrado ou não está mais ativo.</p></div></section>';layout_footer();exit;}

$redirectTo = $emergencyService->redirectForLoggedActor($student, $_SESSION, $token);
if ($redirectTo) redirect($redirectTo);

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $emergencyService->createPublicAlert($student, $token, $_POST, (string)($_SERVER['REMOTE_ADDR'] ?? ''), (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $sent=true;
}
layout_header('Emergência');
?>
<section class="emergency-page"><article class="emergency-card"><div class="emergency-brand"><img src="<?=e(asset_url('assets/porta-aberta-logo.jpg'))?>" alt="<?=e(app_name())?>"><strong><?=e(app_name())?></strong></div><?php if(!empty($sent)):?><div class="family-finished success emergency-success"><span>✓</span><h1>Alerta enviado</h1><p>A escola recebeu seu aviso.</p></div><?php else:?><span class="emergency-icon">!</span><h1>Crachá de segurança</h1><p class="emergency-lead">Crachá vinculado a uma criança cadastrada em uma escola.</p><p>Se você encontrou esta criança desacompanhada ou em situação de risco, alerte a escola.</p><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" id="latitude" name="latitude"><input type="hidden" id="longitude" name="longitude"><label class="form-label" for="nome">Seu nome <small>(opcional)</small></label><input id="nome" class="form-control form-control-lg" name="nome" maxlength="150"><label class="form-label mt-3" for="telefone">Seu telefone <small>(opcional)</small></label><input id="telefone" class="form-control form-control-lg" name="telefone" inputmode="tel" maxlength="30"><label class="form-label mt-3" for="mensagem">Mensagem <small>(opcional)</small></label><textarea id="mensagem" class="form-control" name="mensagem" rows="3" maxlength="500"></textarea><button id="location-button" class="btn btn-outline-secondary w-100 mt-3" type="button">Compartilhar minha localização</button><button class="btn btn-danger btn-lg w-100 mt-3" type="submit">Alertar escola</button></form><p class="privacy-note">Nenhum dado pessoal da criança é exibido nesta página.</p><?php endif?></article></section>
<script nonce="<?=e(csp_nonce())?>">const locationButton=document.querySelector('#location-button');if(locationButton)locationButton.addEventListener('click',()=>{if(!navigator.geolocation){locationButton.textContent='Localização indisponível';return}locationButton.disabled=true;navigator.geolocation.getCurrentPosition(position=>{document.querySelector('#latitude').value=position.coords.latitude;document.querySelector('#longitude').value=position.coords.longitude;locationButton.textContent='Localização adicionada ✓'},()=>{locationButton.disabled=false;locationButton.textContent='Não foi possível obter a localização'})});</script>
<?php layout_footer();
