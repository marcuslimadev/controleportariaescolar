<?php
require __DIR__.'/../../includes/bootstrap.php';
require_role(['admin','portaria']);
$id=(int)($_GET['id']??0);
$q=db()->prepare('SELECT a.*,t.nome turma FROM scp_alunos a LEFT JOIN scp_turmas t ON t.id=a.turma_id WHERE a.id=?');
$q->execute([$id]);
$a=$q->fetch();
if(!$a){http_response_code(404);exit('Aluno não encontrado');}
$telefone='';
if(!empty($_GET['convite'])){$phoneQuery=db()->prepare('SELECT telefone FROM scp_convites_cadastro WHERE id=? AND aluno_id=?');$phoneQuery->execute([(int)$_GET['convite'],$id]);$telefone=normalize_phone((string)$phoneQuery->fetchColumn());}
if(!$telefone){$phoneQuery=db()->prepare('SELECT r.telefone FROM scp_aluno_responsavel ar JOIN scp_responsaveis r ON r.id=ar.responsavel_id WHERE ar.aluno_id=? ORDER BY ar.autoriza_retirada DESC LIMIT 1');$phoneQuery->execute([$id]);$telefone=normalize_phone((string)$phoneQuery->fetchColumn());}
$publicCard=url('cracha.php?token='.$a['qr_token']);
if(($_GET['emit']??'')==='1'){
    db()->prepare('INSERT INTO scp_crachas_emitidos(aluno_id,emitido_por,token_no_momento) VALUES(?,?,?)')->execute([$id,$_SESSION['user_id'],$a['qr_token']]);
    audit('emitir_cracha','scp_alunos',$id);
    redirect('admin/cracha.php?id='.$id.'&ready=1');
}
layout_header('Crachá de '.$a['nome']);
?>
<section class="badge-page">
  <div class="text-center mb-3">
    <span class="gate-eyebrow">CRACHÁ DIGITAL</span>
    <h1 class="h2 fw-bold mt-1">Pronto para enviar</h1>
    <p class="text-muted">Baixe a imagem ou compartilhe direto pelo celular.</p>
  </div>
  <div class="badge-preview-wrap">
    <canvas id="badge-canvas" width="1080" height="1350" aria-label="Crachá de <?=e($a['nome'])?>"></canvas>
    <div id="qrcode-source" hidden></div>
  </div>
  <div class="badge-actions">
    <?php if($telefone):?><a class="btn-whatsapp d-flex align-items-center justify-content-center text-decoration-none" href="https://wa.me/<?=e(str_starts_with($telefone,'55')?$telefone:'55'.$telefone)?>?text=<?=rawurlencode('Olá! O cadastro de '.$a['nome'].' foi aprovado. Este é o crachá digital para usar diariamente na entrada e saída: '.$publicCard)?>" target="_blank" rel="noopener">Enviar crachá ao responsável</a><?php endif?>
    <button id="share-badge" class="btn-whatsapp" type="button"><span aria-hidden="true">●</span> Compartilhar no WhatsApp</button>
    <button id="download-badge" class="btn btn-outline-primary btn-lg w-100" type="button">Baixar imagem do crachá</button>
    <a class="btn btn-link" href="<?=e(url('portaria/cadastro.php'))?>">Cadastrar próximo aluno</a>
  </div>
  <p id="share-status" class="scanner-status" role="status" aria-live="polite"></p>
</section>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
const badgeData={
  name:<?=json_encode($a['nome'],JSON_UNESCAPED_UNICODE)?>,
  classroom:<?=json_encode($a['turma']??'Sem turma',JSON_UNESCAPED_UNICODE)?>,
  photo:<?=json_encode($a['foto']??'')?>,
  token:<?=json_encode($a['qr_token'])?>,
  publicUrl:<?=json_encode($publicCard)?>,
  school:<?=json_encode(config()['app_name']??'SCP Escolar',JSON_UNESCAPED_UNICODE)?>
};
const canvas=document.querySelector('#badge-canvas');
const ctx=canvas.getContext('2d');
const shareStatus=document.querySelector('#share-status');
new QRCode(document.querySelector('#qrcode-source'),{text:badgeData.token,width:360,height:360,correctLevel:QRCode.CorrectLevel.H});

function roundedRect(x,y,w,h,r,fill){ctx.beginPath();ctx.roundRect(x,y,w,h,r);ctx.fillStyle=fill;ctx.fill()}
function fitText(text,maxWidth,startSize,minSize){let size=startSize;do{ctx.font=`800 ${size}px system-ui,-apple-system,sans-serif`;if(ctx.measureText(text).width<=maxWidth)return size;size-=2}while(size>minSize);return minSize}
function loadPhoto(){return new Promise(resolve=>{if(!badgeData.photo)return resolve(null);const img=new Image();img.crossOrigin='anonymous';img.onload=()=>resolve(img);img.onerror=()=>resolve(null);img.src=badgeData.photo})}

async function drawBadge(){
  const photo=await loadPhoto();
  const qr=document.querySelector('#qrcode-source canvas');
  ctx.clearRect(0,0,1080,1350);
  ctx.fillStyle='#eef7fb';ctx.fillRect(0,0,1080,1350);
  roundedRect(55,45,970,1260,54,'#ffffff');
  ctx.fillStyle='#075985';ctx.fillRect(55,45,970,225);
  ctx.fillStyle='#ffffff';ctx.textAlign='center';ctx.font='800 62px system-ui,-apple-system,sans-serif';ctx.fillText(badgeData.school,540,145);
  ctx.font='700 27px system-ui,-apple-system,sans-serif';ctx.fillStyle='#bae6fd';ctx.fillText('IDENTIFICAÇÃO ESCOLAR',540,202);
  ctx.save();ctx.beginPath();ctx.arc(540,405,132,0,Math.PI*2);ctx.clip();
  if(photo){const scale=Math.max(264/photo.width,264/photo.height);const w=photo.width*scale,h=photo.height*scale;ctx.drawImage(photo,540-w/2,405-h/2,w,h)}else{ctx.fillStyle='#dbeafe';ctx.fillRect(408,273,264,264);ctx.fillStyle='#075985';ctx.font='900 105px system-ui';ctx.fillText(badgeData.name.trim().charAt(0).toUpperCase(),540,442)}
  ctx.restore();ctx.strokeStyle='#ffffff';ctx.lineWidth=12;ctx.beginPath();ctx.arc(540,405,138,0,Math.PI*2);ctx.stroke();
  ctx.fillStyle='#102235';ctx.font=`800 ${fitText(badgeData.name,860,58,36)}px system-ui,-apple-system,sans-serif`;ctx.fillText(badgeData.name,540,625);
  roundedRect(335,665,410,72,36,'#e0f2fe');ctx.fillStyle='#075985';ctx.font='800 31px system-ui,-apple-system,sans-serif';ctx.fillText(badgeData.classroom,540,712);
  roundedRect(330,780,420,420,32,'#f8fafc');ctx.drawImage(qr,360,810,360,360);
  ctx.fillStyle='#64748b';ctx.font='600 23px system-ui,-apple-system,sans-serif';ctx.fillText('Apresente este QR Code na portaria',540,1250);
}

function badgeBlob(){return new Promise(resolve=>canvas.toBlob(resolve,'image/png',1))}
function filename(){return 'cracha-'+badgeData.name.normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/gi,'-').replace(/^-|-$/g,'').toLowerCase()+'.png'}
async function downloadBadge(){await badgeReady;const blob=await badgeBlob();const link=document.createElement('a');link.href=URL.createObjectURL(blob);link.download=filename();link.click();setTimeout(()=>URL.revokeObjectURL(link.href),1000)}

document.querySelector('#download-badge').addEventListener('click',downloadBadge);
document.querySelector('#share-badge').addEventListener('click',async()=>{
  const button=document.querySelector('#share-badge');button.disabled=true;shareStatus.textContent='Preparando imagem…';
  try{
    await badgeReady;
    const blob=await badgeBlob();const file=new File([blob],filename(),{type:'image/png'});
    if(navigator.share&&navigator.canShare&&navigator.canShare({files:[file]})){
      await navigator.share({files:[file],title:'Crachá de '+badgeData.name,text:'Crachá escolar de '+badgeData.name});shareStatus.textContent='Crachá compartilhado.';
    }else{
      await downloadBadge();shareStatus.textContent='Imagem baixada. Anexe-a na conversa do WhatsApp que será aberta.';
      window.open('https://wa.me/?text='+encodeURIComponent('Olá! Segue o crachá escolar de '+badgeData.name+'.'),'_blank','noopener');
    }
  }catch(error){if(error.name!=='AbortError')shareStatus.textContent='Não foi possível compartilhar. Use o botão “Baixar imagem”.'}finally{button.disabled=false}
});
const badgeReady=drawBadge();
</script>
<?php layout_footer();
