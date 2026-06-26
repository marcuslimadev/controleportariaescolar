<?php
require __DIR__.'/../../includes/bootstrap.php';
require_permission('student.quick_create');

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $nome=trim((string)($_POST['nome']??''));
    $turmaId=(int)($_POST['turma_id']??0);
    $cpf=preg_replace('/\D/','',(string)($_POST['cpf']??''));
    $nascimento=trim((string)($_POST['data_nascimento']??''));
    $parentesco=trim((string)($_POST['parentesco']??'')) ?: 'Responsável';
    $respNome=trim((string)($_POST['responsavel_nome']??''));
    $respCpf=preg_replace('/\D/','',(string)($_POST['responsavel_cpf']??''));
    $respTelefone=normalize_phone((string)($_POST['responsavel_telefone']??''));
    try{
        if(strlen($nome)<3)throw new RuntimeException('Informe o nome completo do aluno.');
        if(strlen($respNome)<3)throw new RuntimeException('Informe o nome completo do responsável.');
        if(strlen($respCpf)!==11)throw new RuntimeException('Informe um CPF válido para o responsável (11 números).');
        if(strlen($respTelefone)<10)throw new RuntimeException('Informe um telefone com DDD para o responsável.');
        if($turmaId){$check=db()->prepare('SELECT COUNT(*) FROM scp_turmas WHERE id=? AND ativo=1');$check->execute([$turmaId]);if(!$check->fetchColumn())throw new RuntimeException('Selecione uma turma válida.');}
        $foto=null;
        if(isset($_FILES['foto'])&&($_FILES['foto']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
            if($_FILES['foto']['error']!==UPLOAD_ERR_OK)throw new RuntimeException('Não foi possível receber a foto do aluno.');
            if($_FILES['foto']['size']>8*1024*1024)throw new RuntimeException('A foto do aluno deve ter no máximo 8 MB.');
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['foto']['tmp_name']);
            $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if(!isset($extensions[$mime]))throw new RuntimeException('Use uma foto JPG, PNG ou WebP para o aluno.');
            $directory=__DIR__.'/../uploads/alunos';
            if(!is_dir($directory)&&!mkdir($directory,0755,true))throw new RuntimeException('Não foi possível preparar a pasta de fotos.');
            $filename=bin2hex(random_bytes(16)).'.'.$extensions[$mime];
            if(!move_uploaded_file($_FILES['foto']['tmp_name'],$directory.'/'.$filename))throw new RuntimeException('Não foi possível salvar a foto do aluno.');
            $foto=url('uploads/alunos/'.$filename);
        }
        $respFoto=null;
        if(isset($_FILES['responsavel_foto'])&&($_FILES['responsavel_foto']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
            $respFoto=save_uploaded_image($_FILES['responsavel_foto'],'responsaveis');
        }

        $pdo=db();$pdo->beginTransaction();
        $q=$pdo->prepare('SELECT id FROM scp_responsaveis WHERE cpf=? LIMIT 1');$q->execute([$respCpf]);
        $responsavelId=(int)$q->fetchColumn();
        if($responsavelId){
            $q=$pdo->prepare('UPDATE scp_responsaveis SET telefone=?,foto=COALESCE(?,foto),ativo=1 WHERE id=?');
            $q->execute([$respTelefone,$respFoto,$responsavelId]);
        }else{
            $q=$pdo->prepare('INSERT INTO scp_responsaveis(nome,cpf,telefone,foto,senha_hash) VALUES(?,?,?,?,?)');
            $q->execute([$respNome,$respCpf,$respTelefone,$respFoto,password_hash(bin2hex(random_bytes(8)),PASSWORD_DEFAULT)]);
            $responsavelId=(int)$pdo->lastInsertId();
        }
        $q=$pdo->prepare('INSERT INTO scp_alunos(nome,cpf,data_nascimento,turma_id,foto,qr_token) VALUES(?,?,?,?,?,?)');
        $q->execute([$nome,$cpf?:null,$nascimento?:null,$turmaId?:null,$foto,bin2hex(random_bytes(32))]);
        $alunoId=(int)$pdo->lastInsertId();
        $q=$pdo->prepare('INSERT INTO scp_aluno_responsavel(aluno_id,responsavel_id,parentesco,autoriza_consulta,autoriza_retirada) VALUES(?,?,?,1,1)');
        $q->execute([$alunoId,$responsavelId,$parentesco]);
        $pdo->commit();
        audit('cadastro_rapido_aluno','scp_alunos',$alunoId,['responsavel_id'=>$responsavelId]);
        redirect('admin/cracha.php?responsavel_id='.$responsavelId.'&emit=1');
    }catch(Throwable $error){
        if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();
        $message=$error instanceof PDOException?'Não foi possível cadastrar. Verifique se algum CPF já está em uso.':$error->getMessage();
    }
}
$turmas=db()->query('SELECT id,nome,turno FROM scp_turmas WHERE ativo=1 ORDER BY nome')->fetchAll();
layout_header('Cadastro rápido');
?>
<section class="quick-register">
  <div class="text-center mb-4"><span class="gate-eyebrow">PORTARIA</span><h1>Cadastro rápido</h1><p>Aluno e responsável juntos. O crachá é gerado na hora.</p></div>
  <?php if(isset($message)):?><div class="alert alert-danger" role="alert"><?=e($message)?></div><?php endif?>
  <form method="post" enctype="multipart/form-data" class="quick-register-card">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <label class="form-label fw-bold" for="nome">Nome completo do aluno <span class="text-danger">*</span></label>
    <input id="nome" class="form-control form-control-lg mb-3" name="nome" value="<?=e($_POST['nome']??'')?>" autocomplete="off" required autofocus>
    <label class="photo-capture" for="foto"><span class="photo-camera" aria-hidden="true"></span><strong>Foto do aluno</strong><small>Usar câmera ou galeria</small><img id="photo-preview" alt="Prévia da foto"></label>
    <input id="foto" class="visually-hidden" type="file" name="foto" accept="image/jpeg,image/png,image/webp" capture="environment">
    <div class="row g-3 mt-1">
      <div class="col-sm-6"><label class="form-label fw-bold" for="turma_id">Turma</label><select id="turma_id" class="form-select form-select-lg" name="turma_id"><option value="">Sem turma</option><?php foreach($turmas as $t):?><option value="<?=$t['id']?>" <?=((string)($t['id'])===($_POST['turma_id']??''))?'selected':''?>><?=e($t['nome'])?> · <?=e(ucfirst($t['turno']))?></option><?php endforeach?></select></div>
      <div class="col-sm-6"><label class="form-label fw-bold" for="data_nascimento">Nascimento</label><input id="data_nascimento" class="form-control form-control-lg" type="date" name="data_nascimento" value="<?=e($_POST['data_nascimento']??'')?>"></div>
      <div class="col-12"><label class="form-label fw-bold" for="cpf">CPF do aluno <span class="text-muted fw-normal">(opcional)</span></label><input id="cpf" class="form-control form-control-lg" inputmode="numeric" name="cpf" value="<?=e($_POST['cpf']??'')?>" placeholder="Somente números"></div>
    </div>

    <hr class="my-4">
    <label class="form-label fw-bold" for="responsavel_nome">Nome do responsável <span class="text-danger">*</span></label>
    <small class="d-block text-muted mb-2">Se o CPF já estiver cadastrado, só vinculamos este aluno à conta existente.</small>
    <input id="responsavel_nome" class="form-control form-control-lg mb-3" name="responsavel_nome" value="<?=e($_POST['responsavel_nome']??'')?>" autocomplete="off" required>
    <label class="photo-capture" for="responsavel_foto"><span class="photo-camera" aria-hidden="true"></span><strong>Foto do responsável</strong><small>Usar câmera ou galeria</small><img id="responsavel-photo-preview" alt="Prévia da foto do responsável"></label>
    <input id="responsavel_foto" class="visually-hidden" type="file" name="responsavel_foto" accept="image/jpeg,image/png,image/webp" capture="user">
    <div class="row g-3 mt-1">
      <div class="col-sm-6"><label class="form-label fw-bold" for="responsavel_cpf">CPF do responsável <span class="text-danger">*</span></label><input id="responsavel_cpf" class="form-control form-control-lg" inputmode="numeric" name="responsavel_cpf" value="<?=e($_POST['responsavel_cpf']??'')?>" placeholder="Somente números" required></div>
      <div class="col-sm-6"><label class="form-label fw-bold" for="responsavel_telefone">WhatsApp do responsável <span class="text-danger">*</span></label><input id="responsavel_telefone" class="form-control form-control-lg" inputmode="tel" name="responsavel_telefone" value="<?=e($_POST['responsavel_telefone']??'')?>" placeholder="DDD + número" required></div>
      <div class="col-12"><label class="form-label fw-bold" for="parentesco">Parentesco</label><input id="parentesco" class="form-control form-control-lg" name="parentesco" value="<?=e($_POST['parentesco']??'')?>" placeholder="Pai, mãe, motorista…"></div>
    </div>
    <button class="btn-scan mt-4" type="submit">Cadastrar e gerar crachá</button>
  </form>
  <div class="text-center mt-3"><a class="btn btn-link" href="<?=e(url('portaria/index.php'))?>">Voltar ao leitor</a></div>
</section>
<script>
const photoInput=document.querySelector('#foto'),photoPreview=document.querySelector('#photo-preview');
photoInput.addEventListener('change',()=>{const file=photoInput.files[0];if(!file)return;photoPreview.src=URL.createObjectURL(file);photoPreview.classList.add('show');document.querySelector('label[for="foto"] strong').textContent='Trocar foto';});
const respPhotoInput=document.querySelector('#responsavel_foto'),respPhotoPreview=document.querySelector('#responsavel-photo-preview');
respPhotoInput.addEventListener('change',()=>{const file=respPhotoInput.files[0];if(!file)return;respPhotoPreview.src=URL.createObjectURL(file);respPhotoPreview.classList.add('show');document.querySelector('label[for="responsavel_foto"] strong').textContent='Trocar foto';});
</script>
<?php layout_footer();
