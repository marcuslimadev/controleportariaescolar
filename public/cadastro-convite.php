<?php
require __DIR__.'/../includes/bootstrap.php';
$token=(string)($_GET['token']??$_POST['token']??'');
$onboardingService = \App\Support\ServiceFactory::familyOnboarding();
$state=$onboardingService->getInvite($token);
$invite=$state['invite'];
$invalid=$state['invalid'];
if(!$invite){http_response_code(404);}

if(empty($invalid)&&$_SERVER['REQUEST_METHOD']==='POST'&&$invite['status']==='aguardando'){
    verify_csrf();
    try{
        $responsavelFoto=save_uploaded_image($_FILES['responsavel_foto']??[],'convites');
        $alunoFoto=save_uploaded_image($_FILES['aluno_foto']??[],'convites');
        $onboardingService->fillInvite((int)$invite['id'], $_POST, $responsavelFoto, $alunoFoto);
        $invite['status']='preenchido';
    }catch(Throwable $error){$message=$error->getMessage();}
}
layout_header('Cadastro da família');
?>
<section class="family-onboarding">
  <?php if(!empty($invalid)):?><div class="family-finished"><span>!</span><h1>Convite indisponível</h1><p>Peça à portaria para gerar um novo QR Code.</p></div>
  <?php elseif($invite['status']==='preenchido'):?><div class="family-finished success"><span>✓</span><h1>Cadastro enviado</h1><p>Avise ao agente da portaria. Ele fará a conferência e enviará o crachá pelo WhatsApp.</p></div>
  <?php elseif($invite['status']==='aprovado'):?><div class="family-finished success"><span>✓</span><h1>Cadastro aprovado</h1><p>Seu crachá digital já foi liberado pela escola.</p><?php if($invite['responsavel_id']):?><a class="btn-scan text-decoration-none" href="<?=e(url('cracha.php?responsavel='.$invite['responsavel_id'].'&convite='.$token))?>">Abrir crachá digital</a><?php endif?></div>
  <?php else:?>
  <div class="onboarding-heading text-center"><span class="gate-eyebrow">CADASTRO SEGURO</span><h1>Vamos criar o crachá</h1><p>Você tira as fotos e define sua senha. A portaria apenas confere e aprova.</p></div>
  <?php if(isset($message)):?><div class="alert alert-danger"><?=e($message)?></div><?php endif?>
  <form method="post" enctype="multipart/form-data" class="onboarding-form"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="token" value="<?=e($token)?>">
    <fieldset><legend><span>1</span> Dados do responsável</legend><label class="form-label" for="responsavel_nome">Seu nome completo</label><input id="responsavel_nome" class="form-control form-control-lg" name="responsavel_nome" value="<?=e($_POST['responsavel_nome']??'')?>" minlength="3" required><div class="row g-3 mt-1"><div class="col-sm-6"><label class="form-label" for="cpf">CPF</label><input id="cpf" class="form-control form-control-lg" name="cpf" inputmode="numeric" value="<?=e($_POST['cpf']??'')?>" required></div><div class="col-sm-6"><label class="form-label" for="email">E-mail <small>(opcional)</small></label><input id="email" type="email" class="form-control form-control-lg" name="email" value="<?=e($_POST['email']??'')?>"></div></div><p class="phone-confirm">WhatsApp informado pela portaria: <strong>•••• <?=e(substr($invite['telefone'],-4))?></strong></p></fieldset>
    <fieldset><legend><span>2</span> Fotos pelo celular</legend><div class="photo-grid"><label class="selfie-card" for="responsavel_foto"><strong>Sua foto</strong><small>Rosto bem iluminado</small><span>📷 Tirar selfie</span><img id="responsavel-preview" alt="Sua foto"></label><input id="responsavel_foto" class="visually-hidden" type="file" name="responsavel_foto" accept="image/jpeg,image/png,image/webp" capture="user" required><label class="selfie-card" for="aluno_foto"><strong>Foto da criança</strong><small>Rosto bem visível</small><span>📷 Tirar foto</span><img id="aluno-preview" alt="Foto da criança"></label><input id="aluno_foto" class="visually-hidden" type="file" name="aluno_foto" accept="image/jpeg,image/png,image/webp" capture="environment" required></div></fieldset>
    <fieldset><legend><span>3</span> Dados da criança</legend><label class="form-label" for="aluno_nome">Nome completo</label><input id="aluno_nome" class="form-control form-control-lg" name="aluno_nome" value="<?=e($_POST['aluno_nome']??'')?>" minlength="3" required><label class="form-label mt-3" for="data_nascimento">Data de nascimento</label><input id="data_nascimento" type="date" class="form-control form-control-lg" name="data_nascimento" value="<?=e($_POST['data_nascimento']??'')?>"></fieldset>
    <fieldset><legend><span>4</span> Crie sua senha</legend><div class="row g-3"><div class="col-sm-6"><label class="form-label" for="senha">Senha</label><input id="senha" type="password" class="form-control form-control-lg" name="senha" minlength="8" autocomplete="new-password" required></div><div class="col-sm-6"><label class="form-label" for="confirmar_senha">Repetir senha</label><input id="confirmar_senha" type="password" class="form-control form-control-lg" name="confirmar_senha" minlength="8" autocomplete="new-password" required></div></div><small class="text-muted">Use pelo menos 8 caracteres.</small></fieldset>
    <button class="btn-scan" type="submit">Concluir e avisar a portaria</button><p class="privacy-note">🔒 Seus dados e fotos serão enviados somente para a escola.</p>
  </form>
  <?php endif?>
</section>
<script nonce="<?=e(csp_nonce())?>">
for(const [inputId,previewId] of [['responsavel_foto','responsavel-preview'],['aluno_foto','aluno-preview']]){const input=document.getElementById(inputId);if(input)input.addEventListener('change',()=>{const file=input.files[0];if(!file)return;const preview=document.getElementById(previewId);preview.src=URL.createObjectURL(file);preview.classList.add('show')})}
const onboardingForm=document.querySelector('.onboarding-form');
if(onboardingForm){
  const cpfInput=document.getElementById('cpf');
  const senhaInput=document.getElementById('senha');
  const confirmarInput=document.getElementById('confirmar_senha');
  const clearCustom=()=>{cpfInput.setCustomValidity('');confirmarInput.setCustomValidity('')};
  [cpfInput,senhaInput,confirmarInput].forEach(el=>el.addEventListener('input',clearCustom));
  onboardingForm.addEventListener('submit',event=>{
    cpfInput.setCustomValidity(cpfInput.value.replace(/\D/g,'').length===11?'':'Informe um CPF com 11 números.');
    confirmarInput.setCustomValidity(senhaInput.value===confirmarInput.value?'':'As senhas não coincidem.');
    if(!onboardingForm.checkValidity()){event.preventDefault();onboardingForm.reportValidity()}
  });
}
</script>
<?php layout_footer();
