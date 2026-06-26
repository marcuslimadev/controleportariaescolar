<?php
require __DIR__.'/../../includes/bootstrap.php';
require_permission('invite.manage');
$inviteService = new \App\Services\InviteService(
    new \App\Infrastructure\Persistence\PdoInviteRepository(db()),
    new \App\Infrastructure\Persistence\PdoGuardianRepository(db()),
    new \App\Infrastructure\Persistence\PdoStudentRepository(db()),
    new \App\Infrastructure\Logging\DatabaseAuditLogger(),
    db(),
);

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=$_POST['action']??'';
    try{
        if($action==='criar'){
            $recentInvite = $inviteService->createInvite((string)($_POST['telefone']??''), (int)$_SESSION['user_id']);
            $_SESSION['convite_recente']=$recentInvite;
            redirect('portaria/convites.php?convite='.$recentInvite['id']);
        }
        if($action==='aprovar'){
            $id=(int)($_POST['id']??0);
            $result=$inviteService->approveInvite($id, (int)$_SESSION['user_id']);
            redirect('admin/cracha.php?responsavel_id='.$result['responsavel_id'].'&convite='.$id.'&ready=1');
        }
    }catch(Throwable $error){$message=$error->getMessage();}
}

$recent=$_SESSION['convite_recente']??null;
if(!$recent||$recent['id']!==(int)($_GET['convite']??0))$recent=null;else unset($_SESSION['convite_recente']);
$invites=$inviteService->refreshPendingList(30);
layout_header('Convites de cadastro');
?>
<section class="invite-page">
  <div class="invite-heading"><div><span class="gate-eyebrow">PORTARIA</span><h1>Cadastro pelo responsável</h1><p>Peça apenas o WhatsApp e entregue o restante para a família preencher.</p></div><a class="btn btn-outline-primary" href="<?=e(url('portaria/index.php'))?>">Voltar ao leitor</a></div>
  <?php if(isset($message)):?><div class="alert alert-danger"><?=e($message)?></div><?php endif?>
  <form method="post" class="invite-create-card"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="criar"><label class="form-label fw-bold" for="telefone">WhatsApp do pai ou responsável</label><div class="input-group input-group-lg"><span class="input-group-text">+55</span><input id="telefone" class="form-control" name="telefone" inputmode="tel" placeholder="DDD + número" required><button class="btn btn-success">Gerar convite</button></div><small class="text-muted">O convite expira em 24 horas.</small></form>

  <?php if($recent):$inviteUrl=url('cadastro-convite.php?token='.$recent['token']);$phone=normalize_phone($recent['telefone']);if(!str_starts_with($phone,'55'))$phone='55'.$phone;$waUrl='https://wa.me/'.$phone.'?text='.rawurlencode('Olá! A portaria iniciou seu cadastro escolar. Abra este link para tirar as fotos, cadastrar a criança e criar sua senha: '.$inviteUrl);?>
  <article class="invite-qr-card">
    <div class="success-check">✓</div><h2>Convite pronto</h2><p>Mostre este QR Code para o responsável ler agora.</p><div id="invite-qrcode"></div>
    <a class="btn-whatsapp d-flex align-items-center justify-content-center text-decoration-none mt-3" href="<?=e($waUrl)?>" target="_blank" rel="noopener">Enviar convite pelo WhatsApp</a>
    <label class="form-label small text-muted mt-3 mb-1 d-block text-start" for="wa-link-field">Link do WhatsApp</label>
    <div class="input-group input-group-sm"><input type="text" class="form-control" readonly value="<?=e($waUrl)?>" id="wa-link-field" aria-label="Link do WhatsApp" onclick="this.select()"><button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('wa-link-field').value)">Copiar</button></div>
    <button class="btn btn-outline-secondary w-100 mt-2" type="button" onclick="navigator.clipboard.writeText(<?=e(json_encode($inviteUrl))?>)">Copiar link do cadastro</button>
  </article>
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script><script>new QRCode(document.querySelector('#invite-qrcode'),{text:<?=json_encode($inviteUrl)?>,width:230,height:230,correctLevel:QRCode.CorrectLevel.M});</script>
  <?php endif?>

  <div class="d-flex align-items-center justify-content-between mt-4 mb-2"><h2 class="h4 mb-0">Acompanhamento</h2><span class="pending-live"><i></i> Atualização automática</span></div>
  <div class="invite-list" id="invite-list">
    <?php if(!$invites):?><div class="empty-state">Nenhum convite aguardando.</div><?php endif?>
    <?php foreach($invites as $item):?><article class="invite-item <?=$item['status']==='preenchido'?'ready':''?>"><div><span class="invite-status"><?=$item['status']==='preenchido'?'Cadastro concluído':'Aguardando responsável'?></span><h3><?=$item['aluno_nome']?e($item['aluno_nome']):'WhatsApp •••• '.e(substr($item['telefone'],-4))?></h3><p><?=$item['responsavel_nome']?'Responsável: '.e($item['responsavel_nome']):'Convite criado '.e(date('d/m H:i',strtotime($item['created_at'])))?></p></div><?php if($item['status']==='preenchido'):?><a class="btn btn-success btn-lg" href="<?=e(url('portaria/aprovar.php?id='.$item['id']))?>">Revisar e aprovar</a><?php endif?></article><?php endforeach?>
  </div>
</section>
<script>
const telefoneInput=document.getElementById('telefone');
setInterval(async()=>{
  try{
    const response=await fetch('pendencias.php');
    const data=await response.json();
    const isTyping=document.activeElement===telefoneInput||(telefoneInput&&telefoneInput.value.trim()!=='');
    if(data.latest&&data.latest!==<?=json_encode($invites[0]['preenchido_em']??null)?>&&!isTyping)location.reload();
  }catch(error){}
},12000);
</script>
<?php layout_footer();
