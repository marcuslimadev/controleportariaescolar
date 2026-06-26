<?php
require __DIR__.'/../../includes/bootstrap.php';
require_permission('invite.manage');

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=$_POST['action']??'';
    try{
        if($action==='criar'){
            $telefone=normalize_phone((string)($_POST['telefone']??''));
            if(strlen($telefone)<10||strlen($telefone)>13)throw new RuntimeException('Informe um telefone com DDD.');
            $token=bin2hex(random_bytes(32));
            $q=db()->prepare("INSERT INTO scp_convites_cadastro(telefone,token_hash,criado_por,expira_em) VALUES(?,?,?,DATE_ADD(NOW(),INTERVAL 24 HOUR))");
            $q->execute([$telefone,hash('sha256',$token),$_SESSION['user_id']]);
            $id=(int)db()->lastInsertId();audit('criar_convite_cadastro','scp_convites_cadastro',$id,['telefone_final'=>substr($telefone,-4)]);
            $_SESSION['convite_recente']=['id'=>$id,'token'=>$token,'telefone'=>$telefone];
            redirect('portaria/convites.php?convite='.$id);
        }
        if($action==='aprovar'){
            $id=(int)($_POST['id']??0);$pdo=db();$pdo->beginTransaction();
            $q=$pdo->prepare("SELECT * FROM scp_convites_cadastro WHERE id=? AND status='preenchido' AND expira_em>NOW() FOR UPDATE");$q->execute([$id]);$invite=$q->fetch();
            if(!$invite)throw new RuntimeException('Este cadastro não está pronto para aprovação.');
            $q=$pdo->prepare('SELECT id FROM scp_responsaveis WHERE cpf=? LIMIT 1');$q->execute([$invite['responsavel_cpf']]);$responsavelId=(int)$q->fetchColumn();
            if($responsavelId){
                $q=$pdo->prepare('UPDATE scp_responsaveis SET nome=?,email=?,telefone=?,foto=?,senha_hash=?,ativo=1 WHERE id=?');
                $q->execute([$invite['responsavel_nome'],$invite['responsavel_email']?:null,$invite['telefone'],$invite['responsavel_foto'],$invite['senha_hash'],$responsavelId]);
            }else{
                $q=$pdo->prepare('INSERT INTO scp_responsaveis(nome,cpf,email,telefone,foto,senha_hash) VALUES(?,?,?,?,?,?)');
                $q->execute([$invite['responsavel_nome'],$invite['responsavel_cpf'],$invite['responsavel_email']?:null,$invite['telefone'],$invite['responsavel_foto'],$invite['senha_hash']]);$responsavelId=(int)$pdo->lastInsertId();
            }
            $qrToken=bin2hex(random_bytes(32));
            $q=$pdo->prepare('INSERT INTO scp_alunos(nome,data_nascimento,foto,qr_token) VALUES(?,?,?,?)');$q->execute([$invite['aluno_nome'],$invite['aluno_data_nascimento']?:null,$invite['aluno_foto'],$qrToken]);$alunoId=(int)$pdo->lastInsertId();
            $q=$pdo->prepare("INSERT INTO scp_aluno_responsavel(aluno_id,responsavel_id,parentesco,autoriza_consulta,autoriza_retirada) VALUES(?,?,'Responsável',1,1)");$q->execute([$alunoId,$responsavelId]);
            $q=$pdo->prepare("UPDATE scp_convites_cadastro SET status='aprovado',aprovado_em=NOW(),aprovado_por=?,responsavel_id=?,aluno_id=? WHERE id=?");$q->execute([$_SESSION['user_id'],$responsavelId,$alunoId,$id]);
            $pdo->commit();audit('aprovar_cadastro_responsavel','scp_convites_cadastro',$id,['aluno_id'=>$alunoId]);
            redirect('admin/cracha.php?responsavel_id='.$responsavelId.'&convite='.$id.'&ready=1');
        }
    }catch(Throwable $error){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();$message=$error->getMessage();}
}

db()->exec("UPDATE scp_convites_cadastro SET status='expirado' WHERE status='aguardando' AND expira_em<NOW()");
$recent=$_SESSION['convite_recente']??null;
if(!$recent||$recent['id']!==(int)($_GET['convite']??0))$recent=null;else unset($_SESSION['convite_recente']);
$q=db()->query("SELECT c.*,u.nome criado_por_nome FROM scp_convites_cadastro c JOIN scp_usuarios u ON u.id=c.criado_por WHERE c.status IN ('aguardando','preenchido') ORDER BY FIELD(c.status,'preenchido','aguardando'),c.created_at DESC LIMIT 30");$invites=$q->fetchAll();
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
