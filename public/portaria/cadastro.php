<?php
require __DIR__.'/../../includes/bootstrap.php';
require_role(['admin','portaria']);

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $nome=trim((string)($_POST['nome']??''));
    $turmaId=(int)($_POST['turma_id']??0);
    $cpf=preg_replace('/\D/','',(string)($_POST['cpf']??''));
    $nascimento=trim((string)($_POST['data_nascimento']??''));
    try{
        if(strlen($nome)<3)throw new RuntimeException('Informe o nome completo do aluno.');
        if($turmaId){$check=db()->prepare('SELECT COUNT(*) FROM scp_turmas WHERE id=? AND ativo=1');$check->execute([$turmaId]);if(!$check->fetchColumn())throw new RuntimeException('Selecione uma turma válida.');}
        $foto=null;
        if(isset($_FILES['foto'])&&($_FILES['foto']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
            if($_FILES['foto']['error']!==UPLOAD_ERR_OK)throw new RuntimeException('Não foi possível receber a foto.');
            if($_FILES['foto']['size']>8*1024*1024)throw new RuntimeException('A foto deve ter no máximo 8 MB.');
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['foto']['tmp_name']);
            $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if(!isset($extensions[$mime]))throw new RuntimeException('Use uma foto JPG, PNG ou WebP.');
            $directory=__DIR__.'/../uploads/alunos';
            if(!is_dir($directory)&&!mkdir($directory,0755,true))throw new RuntimeException('Não foi possível preparar a pasta de fotos.');
            $filename=bin2hex(random_bytes(16)).'.'.$extensions[$mime];
            if(!move_uploaded_file($_FILES['foto']['tmp_name'],$directory.'/'.$filename))throw new RuntimeException('Não foi possível salvar a foto.');
            $foto=url('uploads/alunos/'.$filename);
        }
        $q=db()->prepare('INSERT INTO scp_alunos(nome,cpf,data_nascimento,turma_id,foto,qr_token) VALUES(?,?,?,?,?,?)');
        $q->execute([$nome,$cpf?:null,$nascimento?:null,$turmaId?:null,$foto,bin2hex(random_bytes(32))]);
        $id=(int)db()->lastInsertId();audit('cadastro_rapido_aluno','scp_alunos',$id);
        redirect('admin/cracha.php?id='.$id.'&emit=1');
    }catch(Throwable $error){$message=$error instanceof PDOException?'Não foi possível cadastrar. Verifique se CPF já está em uso.':$error->getMessage();}
}
$turmas=db()->query('SELECT id,nome,turno FROM scp_turmas WHERE ativo=1 ORDER BY nome')->fetchAll();
layout_header('Cadastro rápido');
?>
<section class="quick-register">
  <div class="text-center mb-4"><span class="gate-eyebrow">PORTARIA</span><h1>Cadastro rápido</h1><p>Preencha o essencial. O crachá será criado em seguida.</p></div>
  <?php if(isset($message)):?><div class="alert alert-danger" role="alert"><?=e($message)?></div><?php endif?>
  <form method="post" enctype="multipart/form-data" class="quick-register-card">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <label class="form-label fw-bold" for="nome">Nome completo <span class="text-danger">*</span></label>
    <input id="nome" class="form-control form-control-lg mb-3" name="nome" value="<?=e($_POST['nome']??'')?>" autocomplete="off" required autofocus>
    <label class="photo-capture" for="foto"><span class="photo-camera" aria-hidden="true"></span><strong>Adicionar foto</strong><small>Usar câmera ou galeria</small><img id="photo-preview" alt="Prévia da foto"></label>
    <input id="foto" class="visually-hidden" type="file" name="foto" accept="image/jpeg,image/png,image/webp" capture="environment">
    <div class="row g-3 mt-1">
      <div class="col-sm-6"><label class="form-label fw-bold" for="turma_id">Turma</label><select id="turma_id" class="form-select form-select-lg" name="turma_id"><option value="">Sem turma</option><?php foreach($turmas as $t):?><option value="<?=$t['id']?>" <?=((string)($t['id'])===($_POST['turma_id']??''))?'selected':''?>><?=e($t['nome'])?> · <?=e(ucfirst($t['turno']))?></option><?php endforeach?></select></div>
      <div class="col-sm-6"><label class="form-label fw-bold" for="data_nascimento">Nascimento</label><input id="data_nascimento" class="form-control form-control-lg" type="date" name="data_nascimento" value="<?=e($_POST['data_nascimento']??'')?>"></div>
      <div class="col-12"><label class="form-label fw-bold" for="cpf">CPF <span class="text-muted fw-normal">(opcional)</span></label><input id="cpf" class="form-control form-control-lg" inputmode="numeric" name="cpf" value="<?=e($_POST['cpf']??'')?>" placeholder="Somente números"></div>
    </div>
    <button class="btn-scan mt-4" type="submit">Cadastrar e gerar crachá</button>
  </form>
  <div class="text-center mt-3"><a class="btn btn-link" href="<?=e(url('portaria/index.php'))?>">Voltar ao leitor</a></div>
</section>
<script>
const photoInput=document.querySelector('#foto'),photoPreview=document.querySelector('#photo-preview');
photoInput.addEventListener('change',()=>{const file=photoInput.files[0];if(!file)return;photoPreview.src=URL.createObjectURL(file);photoPreview.classList.add('show');document.querySelector('.photo-capture strong').textContent='Trocar foto';});
</script>
<?php layout_footer();
