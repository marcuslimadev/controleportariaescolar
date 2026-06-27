<?php
require __DIR__.'/../../includes/bootstrap.php';
require_permission('badge.issue');
$responsavelId=(int)($_GET['responsavel_id']??0);
$badgeService = \App\Support\ServiceFactory::badges();
try {
    $badge = $badgeService->adminGuardianBadge($responsavelId, ($_GET['emit']??'')==='1', (int)$_SESSION['user_id']);
} catch (Throwable $error) {
    http_response_code(404);
    exit($error->getMessage());
}
$r=$badge['guardian'];
$children=$badge['children'];
$publicCard=url('cracha.php?token='.$r['qr_token']);
$telefone=normalize_phone((string)$r['telefone']);

if(($_GET['emit']??'')==='1'){
    redirect('admin/cracha.php?responsavel_id='.$responsavelId.'&ready=1');
}
layout_header('Crachá de '.$r['nome']);
?>
<section class="badge-page">
  <div class="text-center mb-3">
    <span class="gate-eyebrow">CRACHÁ DIGITAL</span>
    <h1 class="h2 fw-bold mt-1">Pronto para enviar</h1>
    <p class="text-muted">Baixe a imagem ou compartilhe direto pelo celular.</p>
  </div>
  <div class="badge-preview-wrap">
    <canvas id="badge-canvas" width="1080" height="1350" aria-label="Crachá de <?=e($r['nome'])?>"></canvas>
    <div id="qrcode-source" hidden></div>
  </div>
  <div class="badge-actions">
    <?php if($telefone):?><a class="btn-whatsapp d-flex align-items-center justify-content-center text-decoration-none" href="https://wa.me/<?=e(str_starts_with($telefone,'55')?$telefone:'55'.$telefone)?>?text=<?=rawurlencode('Olá! Segue seu crachá digital para usar diariamente na entrada e saída: '.$publicCard)?>" target="_blank" rel="noopener">Enviar crachá ao responsável</a><?php endif?>
    <button id="share-badge" class="btn-whatsapp" type="button"><span aria-hidden="true">●</span> Compartilhar no WhatsApp</button>
    <button id="download-badge" class="btn btn-outline-primary btn-lg w-100" type="button">Baixar imagem do crachá</button>
    <a class="btn btn-link" href="<?=e(url('admin/index.php'))?>">Voltar ao painel</a>
  </div>
  <p id="share-status" class="scanner-status" role="status" aria-live="polite"></p>
</section>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script nonce="<?=e(csp_nonce())?>">
const badgeData={
  name:<?=json_encode($r['nome'],JSON_UNESCAPED_UNICODE)?>,
  photo:<?=json_encode($r['foto']??'')?>,
  children:<?=json_encode(array_map(fn($c)=>['nome'=>$c['nome'],'foto'=>$c['foto']??''],$children),JSON_UNESCAPED_UNICODE)?>,
  token:<?=json_encode($r['qr_token'])?>,
  phone:<?=json_encode($telefone?(str_starts_with($telefone,'55')?$telefone:'55'.$telefone):'')?>,
  school:<?=json_encode(app_name(),JSON_UNESCAPED_UNICODE)?>
};
const canvas=document.querySelector('#badge-canvas');
const ctx=canvas.getContext('2d');
const shareStatus=document.querySelector('#share-status');
new QRCode(document.querySelector('#qrcode-source'),{text:badgeData.token,width:360,height:360,correctLevel:QRCode.CorrectLevel.H});

function roundedRect(x,y,w,h,r,fill){ctx.beginPath();ctx.roundRect(x,y,w,h,r);ctx.fillStyle=fill;ctx.fill()}
function fitText(text,maxWidth,startSize,minSize){let size=startSize;do{ctx.font=`800 ${size}px system-ui,-apple-system,sans-serif`;if(ctx.measureText(text).width<=maxWidth)return size;size-=2}while(size>minSize);return minSize}
function loadImage(src){return new Promise(resolve=>{if(!src)return resolve(null);const img=new Image();img.crossOrigin='anonymous';img.onload=()=>resolve(img);img.onerror=()=>resolve(null);img.src=src})}

async function drawBadge(){
  const photo=await loadImage(badgeData.photo);
  const list=badgeData.children.slice(0,4);
  const overflow=badgeData.children.length-list.length;
  const childImages=await Promise.all(list.map(c=>loadImage(c.foto)));
  const qr=document.querySelector('#qrcode-source canvas');
  ctx.clearRect(0,0,1080,1350);
  ctx.fillStyle='#F5F1E6';ctx.fillRect(0,0,1080,1350);
  roundedRect(55,45,970,1260,18,'#ffffff');
  ctx.fillStyle='#1356A2';ctx.fillRect(55,45,970,210);
  ctx.fillStyle='#ffffff';ctx.textAlign='center';ctx.font='800 58px system-ui,-apple-system,sans-serif';ctx.fillText(badgeData.school,540,135);
  ctx.font='700 25px system-ui,-apple-system,sans-serif';ctx.fillStyle='#F2B705';ctx.fillText('CRACHÁ DE RETIRADA',540,188);
  ctx.save();ctx.beginPath();ctx.arc(540,390,120,0,Math.PI*2);ctx.clip();
  if(photo){const scale=Math.max(240/photo.width,240/photo.height);const w=photo.width*scale,h=photo.height*scale;ctx.drawImage(photo,540-w/2,390-h/2,w,h)}else{ctx.fillStyle='#dbeafe';ctx.fillRect(420,270,240,240);ctx.fillStyle='#1356A2';ctx.font='900 96px system-ui';ctx.fillText(badgeData.name.trim().charAt(0).toUpperCase(),540,420)}
  ctx.restore();ctx.strokeStyle='#15171A';ctx.lineWidth=8;ctx.beginPath();ctx.arc(540,390,124,0,Math.PI*2);ctx.stroke();
  ctx.fillStyle='#15171A';ctx.font=`800 ${fitText(badgeData.name,860,52,32)}px system-ui,-apple-system,sans-serif`;ctx.fillText(badgeData.name,540,580);
  roundedRect(330,605,420,62,31,'#F2B705');ctx.fillStyle='#15171A';ctx.font='800 27px system-ui,-apple-system,sans-serif';ctx.fillText('RESPONSÁVEL AUTORIZADO',540,645);

  ctx.font='700 24px system-ui,-apple-system,sans-serif';ctx.fillStyle='#5B5F66';ctx.fillText('AUTORIZADO A RETIRAR',540,705);
  const cell=180,startX=540-(list.length*cell)/2+cell/2,cy=755;
  list.forEach((child,i)=>{
    const cx=startX+i*cell;
    ctx.save();ctx.beginPath();ctx.arc(cx,cy,42,0,Math.PI*2);ctx.clip();
    const img=childImages[i];
    if(img){const scale=Math.max(84/img.width,84/img.height);const w=img.width*scale,h=img.height*scale;ctx.drawImage(img,cx-w/2,cy-h/2,w,h)}else{ctx.fillStyle='#dbeafe';ctx.fillRect(cx-42,cy-42,84,84);ctx.fillStyle='#1356A2';ctx.font='900 36px system-ui';ctx.fillText(child.nome.trim().charAt(0).toUpperCase(),cx,cy+13)}
    ctx.restore();ctx.strokeStyle='#15171A';ctx.lineWidth=4;ctx.beginPath();ctx.arc(cx,cy,44,0,Math.PI*2);ctx.stroke();
    ctx.fillStyle='#15171A';ctx.font='700 22px system-ui,-apple-system,sans-serif';ctx.fillText(child.nome.trim().split(' ')[0],cx,cy+68);
  });
  if(overflow>0){ctx.fillStyle='#5B5F66';ctx.font='700 22px system-ui,-apple-system,sans-serif';ctx.fillText('+'+overflow+' criança(s)',540,cy+95)}
  if(!list.length){ctx.fillStyle='#5B5F66';ctx.font='700 24px system-ui,-apple-system,sans-serif';ctx.fillText('Nenhuma criança vinculada ainda',540,cy)}

  roundedRect(390,930,300,300,16,'#F5F1E6');ctx.drawImage(qr,405,945,270,270);
  ctx.fillStyle='#5B5F66';ctx.font='600 22px system-ui,-apple-system,sans-serif';ctx.fillText('Apresente este QR Code na portaria',540,1260);
}

function badgeBlob(){return new Promise(resolve=>canvas.toBlob(resolve,'image/png',1))}
function filename(){return 'cracha-'+badgeData.name.normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9]+/gi,'-').replace(/^-|-$/g,'').toLowerCase()+'.png'}
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
      const target='https://wa.me/'+(badgeData.phone||'');
      window.open(target+'?text='+encodeURIComponent('Olá! Segue o crachá escolar de '+badgeData.name+'.'),'_blank','noopener');
    }
  }catch(error){if(error.name!=='AbortError')shareStatus.textContent='Não foi possível compartilhar. Use o botão “Baixar imagem”.'}finally{button.disabled=false}
});
const badgeReady=drawBadge();
</script>
<?php layout_footer();
