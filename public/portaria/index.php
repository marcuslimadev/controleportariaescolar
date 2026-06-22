<?php require __DIR__.'/../../includes/bootstrap.php';require_role(['admin','portaria']);$pending=0;try{$pending=(int)db()->query("SELECT COUNT(*) FROM scp_convites_cadastro WHERE status='preenchido'")->fetchColumn();}catch(Throwable $ignored){}layout_header('Portaria');?>
<section class="gate-app" aria-labelledby="gate-title">
  <header class="gate-heading text-center">
    <span class="gate-eyebrow">PORTARIA</span>
    <h1 id="gate-title">Controle de acesso</h1>
    <p id="gate-help">Aponte a câmera para o QR Code do crachá.</p>
    <a class="quick-register-link" href="<?=e(url('portaria/convites.php'))?>">+ Convidar responsável para cadastrar</a>
  </header>

  <a id="pending-banner" class="pending-banner <?=$pending?'':'d-none'?>" href="<?=e(url('portaria/convites.php'))?>"><span id="pending-count"><?=$pending?></span><strong id="pending-title"><?=$pending===1?'Cadastro aguardando aprovação':'Cadastros aguardando aprovação'?></strong><small>Toque para revisar</small></a>

  <div id="scanner-panel" class="scanner-panel">
    <div id="reader" class="qr-reader" aria-label="Visualização da câmera"></div>
    <div id="scanner-placeholder" class="scanner-placeholder">
      <div class="scan-symbol" aria-hidden="true"><span></span></div>
      <strong>Pronto para ler</strong>
      <span>A câmera traseira será usada</span>
    </div>
    <div class="scan-guide" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
  </div>

  <button id="start-scan" class="btn-scan" type="button">
    <span class="camera-icon" aria-hidden="true"></span>
    <span>Escanear crachá</span>
  </button>
  <button id="stop-scan" class="btn btn-outline-secondary w-100 d-none" type="button">Cancelar leitura</button>
  <div id="status" class="scanner-status" role="status" aria-live="polite"></div>
  <div id="result" class="result-area" aria-live="polite"></div>
</section>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script><script>
const csrf=<?=json_encode(csrf())?>;
const reader=new Html5Qrcode('reader');
const startButton=document.querySelector('#start-scan');
const stopButton=document.querySelector('#stop-scan');
const placeholder=document.querySelector('#scanner-placeholder');
const panel=document.querySelector('#scanner-panel');
const statusBox=document.querySelector('#status');
const resultBox=document.querySelector('#result');
let locked=false,scanning=false,current=null;
let pendingCount=<?=$pending?>;

startButton.addEventListener('click',startScanner);
stopButton.addEventListener('click',()=>stopScanner(true));

async function startScanner(){
  if(scanning)return;
  setStatus('Abrindo câmera traseira…','busy');
  startButton.disabled=true;
  resultBox.replaceChildren();
  try{
    await reader.start({facingMode:{exact:'environment'}},{fps:12,qrbox:(w,h)=>{const size=Math.min(w,h)*.72;return{width:size,height:size}},aspectRatio:1},scan);
  }catch(firstError){
    try{await reader.start({facingMode:'environment'},{fps:12,qrbox:{width:250,height:250}},scan)}catch(error){
      startButton.disabled=false;
      setStatus(cameraMessage(error),'error');
      return;
    }
  }
  scanning=true;
  placeholder.classList.add('d-none');
  panel.classList.add('is-scanning');
  startButton.classList.add('d-none');
  stopButton.classList.remove('d-none');
  setStatus('Câmera ativa — aproxime o crachá','active');
}

async function stopScanner(showStart=false){
  if(scanning){try{await reader.stop()}catch(error){} scanning=false;}
  panel.classList.remove('is-scanning');
  placeholder.classList.remove('d-none');
  stopButton.classList.add('d-none');
  startButton.classList.toggle('d-none',!showStart);
  startButton.disabled=false;
  if(showStart)setStatus('','');
}

async function scan(token){
  if(locked)return;
  locked=true;
  if(navigator.vibrate)navigator.vibrate(120);
  setStatus('Crachá encontrado. Consultando…','busy');
  await stopScanner(false);
  try{
    const response=await fetch('lookup.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({csrf,token})});
    show(await response.json());
  }catch(error){
    locked=false;
    showError('Não foi possível consultar. Verifique a conexão e tente novamente.');
  }
}

function show(data){
  if(!data.ok){locked=false;showError('Crachá inválido ou aluno inativo.');return}
  current=data;
  setStatus('','');
  const card=document.createElement('article');card.className='access-card';
  const photo=document.createElement('img');photo.className='student-photo';photo.alt='Foto de '+data.nome;photo.src=data.foto||'';photo.onerror=()=>photo.remove();
  const tag=document.createElement('span');tag.className='access-state '+(data.dentro?'inside':'outside');tag.textContent=data.dentro?'Está dentro':'Está fora';
  const name=document.createElement('h2');name.textContent=data.nome;
  const classroom=document.createElement('p');classroom.className='student-class';classroom.textContent=data.turma||'Sem turma informada';
  const confirm=document.createElement('button');confirm.type='button';confirm.className='btn-confirm '+(data.sugerida==='entrada'?'entry':'exit');confirm.textContent='Confirmar '+labelType(data.sugerida);confirm.addEventListener('click',()=>record(data.token,data.sugerida,false));
  const correction=document.createElement('button');correction.type='button';correction.className='btn-correction';correction.textContent='Corrigir para '+(data.sugerida==='entrada'?'saída':'entrada');correction.addEventListener('click',()=>manual(data.token,data.sugerida==='entrada'?'saida':'entrada'));
  const another=document.createElement('button');another.type='button';another.className='btn-another';another.textContent='Escanear outro crachá';another.addEventListener('click',resetAndScan);
  card.append(photo,tag,name,classroom,confirm,correction,another);resultBox.replaceChildren(card);
}

function manual(token,type){const note=prompt('Informe o motivo da correção (mínimo de 5 caracteres):');if(note&&note.trim().length>=5)record(token,type,true,note)}

async function record(token,type,manual,note=''){
  const button=resultBox.querySelector('.btn-confirm');if(button)button.disabled=true;
  setStatus('Registrando '+labelType(type)+'…','busy');
  try{
    const response=await fetch('registrar.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({csrf,token,tipo:type,manual:manual?'1':'0',observacao:note})});
    const data=await response.json();
    if(!response.ok)throw new Error(data.message||'Falha no registro');
    if(navigator.vibrate)navigator.vibrate([100,60,100]);
    showSuccess(data.message);
  }catch(error){if(button)button.disabled=false;setStatus(error.message||'Não foi possível registrar.','error')}
}

function showSuccess(message){
  const box=document.createElement('div');box.className='success-card';
  const icon=document.createElement('span');icon.className='success-check';icon.textContent='✓';
  const title=document.createElement('h2');title.textContent=message;
  const next=document.createElement('button');next.className='btn-scan';next.type='button';next.textContent='Escanear próximo crachá';next.addEventListener('click',resetAndScan);
  box.append(icon,title,next);resultBox.replaceChildren(box);setStatus('','');
}

function resetAndScan(){locked=false;current=null;resultBox.replaceChildren();startButton.classList.remove('d-none');startScanner()}
function showError(message){setStatus(message,'error');startButton.classList.remove('d-none');startButton.disabled=false;startButton.querySelector('span:last-child').textContent='Tentar novamente'}
function setStatus(message,type){statusBox.textContent=message;statusBox.className='scanner-status '+(type||'')}
function labelType(type){return type==='saida'?'saída':'entrada'}
function cameraMessage(error){const text=String(error&&error.message||error);return /permission|denied|notallowed/i.test(text)?'Permita o acesso à câmera para escanear o crachá.':'Não foi possível abrir a câmera. Verifique se ela está disponível.'}
setInterval(async()=>{try{const response=await fetch('pendencias.php');const data=await response.json();if(data.count>pendingCount&&navigator.vibrate)navigator.vibrate([180,80,180]);pendingCount=data.count;const banner=document.querySelector('#pending-banner');banner.classList.toggle('d-none',!data.count);document.querySelector('#pending-count').textContent=data.count;document.querySelector('#pending-title').textContent=data.count===1?'Cadastro aguardando aprovação':'Cadastros aguardando aprovação'}catch(error){}},12000);
</script><?php layout_footer();
