<?php require __DIR__.'/../../includes/bootstrap.php';require_permission('access.read');$pending=0;try{$pending=(int)(\App\Support\ServiceFactory::invites()->pendingSummary()['count']??0);}catch(Throwable $ignored){}layout_header('Portaria');?>
<section class="gate-app" aria-labelledby="gate-title">
  <header class="gate-heading text-center">
    <span class="gate-eyebrow">PORTARIA</span>
    <h1 id="gate-title">Controle de acesso</h1>
    <p id="gate-help">Aponte a câmera para o QR Code do crachá.</p>
    <a class="quick-register-link" href="<?=e(url('portaria/convites.php'))?>">+ Convidar responsável para cadastrar</a>
  </header>

  <a id="pending-banner" class="pending-banner <?=$pending?'':'d-none'?>" href="<?=e(url('portaria/convites.php'))?>"><span id="pending-count"><?=$pending?></span><strong id="pending-title"><?=$pending===1?'Cadastro aguardando aprovação':'Cadastros aguardando aprovação'?></strong><small>Toque para revisar</small></a>

  <div id="scanner-panel" class="scanner-panel">
    <div id="scanner-state" class="scanner-state" aria-hidden="true">Pronto</div>
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
  <div id="status" class="scanner-status" role="status" aria-live="polite">Pronto para começar.</div>
  <div id="result" class="result-area" aria-live="polite"></div>
</section>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script><script>
const csrf=<?=json_encode(csrf())?>;
const reader=new Html5Qrcode('reader');
const startButton=document.querySelector('#start-scan');
const stopButton=document.querySelector('#stop-scan');
const placeholder=document.querySelector('#scanner-placeholder');
const panel=document.querySelector('#scanner-panel');
const scannerState=document.querySelector('#scanner-state');
const statusBox=document.querySelector('#status');
const resultBox=document.querySelector('#result');
let locked=false,scanning=false,current=null;
let pendingCount=<?=$pending?>;
const initialToken=<?=json_encode(extract_qr_token((string)($_GET['token']??'')))?>;

startButton.addEventListener('click',startScanner);
stopButton.addEventListener('click',()=>stopScanner(true));

async function startScanner(){
  if(scanning)return;
  setScanState('procurando','Abrindo câmera traseira…');
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
  setScanState('procurando','Procurando QR Code — aproxime o crachá');
}

async function stopScanner(showStart=false){
  if(scanning){try{await reader.stop()}catch(error){} scanning=false;}
  panel.classList.remove('is-scanning');
  placeholder.classList.remove('d-none');
  stopButton.classList.add('d-none');
  startButton.classList.toggle('d-none',!showStart);
  startButton.disabled=false;
  if(showStart)setScanState('pronto','Pronto para começar.');
}

async function scan(token){
  if(locked)return;
  locked=true;
  if(navigator.vibrate)navigator.vibrate(120);
  setScanState('encontrado','Crachá encontrado. Consultando autorização…');
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
  if(!data.ok){locked=false;showError(data.message||'Crachá inválido ou aluno inativo.');return}
  current=data;
  const hasExit=data.children.some(child=>child.sugerida==='saida');
  setScanState(hasExit?'saida':'entrada',hasExit?'Responsável encontrado — revise entrada ou saída.':'Responsável encontrado — entrada sugerida.');
  const card=document.createElement('article');card.className='access-card';
  const photo=document.createElement('img');photo.className='student-photo';photo.alt='Foto de '+data.responsavel.nome;photo.src=data.responsavel.foto||'';photo.onerror=()=>photo.remove();
  const tag=document.createElement('span');tag.className='access-state inside';tag.textContent='Responsável autorizado';
  const name=document.createElement('h2');name.textContent=data.responsavel.nome;
  const classroom=document.createElement('p');classroom.className='student-class';classroom.textContent='Selecione as crianças que estão entrando ou saindo.';
  const list=document.createElement('div');list.className='children-list';
  data.children.forEach(child=>list.appendChild(childRow(child)));
  const confirm=document.createElement('button');confirm.type='button';confirm.className='btn-confirm entry';confirm.textContent='Registrar selecionados';confirm.addEventListener('click',()=>record(data.responsavel.token));
  const another=document.createElement('button');another.type='button';another.className='btn-another';another.textContent='Escanear outro crachá';another.addEventListener('click',resetAndScan);
  card.append(photo,tag,name,classroom,list,confirm,another);resultBox.replaceChildren(card);
}

function childRow(child){
  const row=document.createElement('label');row.className='child-row';row.dataset.id=child.id;row.dataset.tipo=child.sugerida;row.dataset.sugerida=child.sugerida;row.dataset.selected='1';
  const check=document.createElement('input');check.type='checkbox';check.checked=true;check.className='visually-hidden';
  const photo=document.createElement('img');photo.alt='Foto de '+child.nome;photo.src=child.foto||'';photo.onerror=()=>photo.remove();
  const info=document.createElement('div');info.className='child-info';
  const name=document.createElement('strong');name.textContent=child.nome;
  const details=document.createElement('span');details.textContent=(child.turma||'Sem turma')+' · '+(child.dentro?'está dentro':'está fora');
  info.append(name,details);
  const toggle=document.createElement('button');toggle.type='button';toggle.className='child-toggle '+child.sugerida;toggle.textContent=labelType(child.sugerida);
  toggle.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();const next=row.dataset.tipo==='entrada'?'saida':'entrada';row.dataset.tipo=next;toggle.textContent=labelType(next);toggle.className='child-toggle '+next});
  row.addEventListener('change',()=>{row.dataset.selected=check.checked?'1':'0';row.classList.toggle('unchecked',!check.checked)});
  row.append(check,photo,info,toggle);
  return row;
}

async function record(token){
  const rows=[...resultBox.querySelectorAll('.child-row')].filter(row=>row.dataset.selected==='1');
  if(!rows.length){setScanState('erro','Selecione pelo menos uma criança.');return}
  const needsNote=rows.some(row=>row.dataset.tipo!==row.dataset.sugerida);
  let note='';
  if(needsNote){
    note=(prompt('Informe o motivo da correção (mínimo de 5 caracteres):')||'').trim();
    if(note.length<5){setScanState('erro','Correção precisa de motivo.');return}
  }
  const items=rows.map(row=>({aluno_id:row.dataset.id,tipo:row.dataset.tipo,manual:row.dataset.tipo!==row.dataset.sugerida,observacao:row.dataset.tipo!==row.dataset.sugerida?note:''}));
  const button=resultBox.querySelector('.btn-confirm');if(button)button.disabled=true;
  const hasExit=items.some(item=>item.tipo==='saida');
  setScanState(hasExit?'saida':'entrada',hasExit?'Registrando saída…':'Registrando entrada…');
  try{
    const response=await fetch('registrar.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({csrf,token,items:JSON.stringify(items)})});
    const data=await response.json();
    if(!response.ok)throw new Error(data.message||'Falha no registro');
    if(navigator.vibrate)navigator.vibrate([100,60,100]);
    showSuccess(data.message);
  }catch(error){if(button)button.disabled=false;setScanState('erro',error.message||'Não foi possível registrar.')}
}

function showSuccess(message){
  const box=document.createElement('div');box.className='success-card';
  const icon=document.createElement('span');icon.className='success-check';icon.textContent='✓';
  const title=document.createElement('h2');title.textContent=message;
  const next=document.createElement('button');next.className='btn-scan';next.type='button';next.textContent='Escanear próximo crachá';next.addEventListener('click',resetAndScan);
  box.append(icon,title,next);resultBox.replaceChildren(box);setScanState('sucesso','Registro concluído.');
}

function resetAndScan(){locked=false;current=null;resultBox.replaceChildren();startButton.classList.remove('d-none');startScanner()}
function showError(message){setScanState('erro',message);startButton.classList.remove('d-none');startButton.disabled=false;startButton.querySelector('span:last-child').textContent='Tentar novamente'}
function setScanState(state,message){
  panel.dataset.state=state||'pronto';
  statusBox.textContent=message||'';
  const typeMap={pronto:'',procurando:'busy',encontrado:'active',entrada:'entry',saida:'exit',erro:'error',sucesso:'success'};
  const labelMap={pronto:'Pronto',procurando:'Procurando',encontrado:'Encontrado',entrada:'Entrada',saida:'Saída',erro:'Erro',sucesso:'Concluído'};
  statusBox.className='scanner-status '+(typeMap[state]||'');
  scannerState.textContent=labelMap[state]||'Pronto';
}
function labelType(type){return type==='saida'?'saída':'entrada'}
function cameraMessage(error){const text=String(error&&error.message||error);return /permission|denied|notallowed/i.test(text)?'Permita o acesso à câmera para escanear o crachá.':'Não foi possível abrir a câmera. Verifique se ela está disponível.'}
setInterval(async()=>{try{const response=await fetch('pendencias.php');const data=await response.json();if(data.count>pendingCount&&navigator.vibrate)navigator.vibrate([180,80,180]);pendingCount=data.count;const banner=document.querySelector('#pending-banner');banner.classList.toggle('d-none',!data.count);document.querySelector('#pending-count').textContent=data.count;document.querySelector('#pending-title').textContent=data.count===1?'Cadastro aguardando aprovação':'Cadastros aguardando aprovação'}catch(error){}},12000);
if(initialToken)scan(initialToken);
</script><?php layout_footer();
