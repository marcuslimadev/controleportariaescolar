<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('post.manage');
$id = (int)($_GET['id'] ?? 0);
$postService = \App\Support\ServiceFactory::posts();
try {
    $formData = $postService->formData($id, (int)$_SESSION['user_id'], (string)($_SESSION['role'] ?? ''));
} catch (Throwable $error) {
    flash($error->getMessage(), 'warning');
    redirect('admin/posts.php');
}
$post = $formData['post'];
$turmas = $formData['classes'];
$alunos = $formData['students'];
layout_header($id ? 'Editar publicação' : 'Nova publicação');
?>
<div class="page-heading"><div><span class="gate-eyebrow">COMUNICAÇÃO</span><h1><?=$id?'Editar publicação':'Nova publicação'?></h1><p>Publique apenas informação oficial e útil para a rotina.</p></div></div>
<section class="section-card portal-form">
<form method="post" action="<?=e(url('admin/post-save.php'))?>" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?=e(csrf())?>">
  <input type="hidden" name="id" value="<?=(int)$post['id']?>">
  <div class="row g-3">
    <div class="col-12"><label class="form-label fw-bold">Título</label><input class="form-control form-control-lg" name="titulo" value="<?=e($post['titulo'])?>" required maxlength="190"></div>
    <div class="col-md-4"><label class="form-label fw-bold">Tipo</label><select class="form-select" name="tipo"><?php foreach(['comunicado','atividade','evento','programação','alerta','cardápio','lembrete'] as $v):?><option value="<?=$v?>" <?=$post['tipo']===$v?'selected':''?>><?=e($v)?></option><?php endforeach?></select></div>
    <div class="col-md-4"><label class="form-label fw-bold">Feed</label><select class="form-select" name="publico"><option value="publico" <?=$post['publico']==='publico'?'selected':''?>>Feed público</option><option value="toda_escola" <?=$post['publico']==='toda_escola'?'selected':''?>>Privado - todos os usuários</option><option value="turma" <?=$post['publico']==='turma'?'selected':''?>>Privado - turma</option><option value="aluno" <?=$post['publico']==='aluno'?'selected':''?>>Privado - aluno</option><option value="equipe" <?=$post['publico']==='equipe'?'selected':''?>>Privado - equipe</option></select><small class="text-muted">Feed público aparece na página inicial. Privado aparece só para usuários logados.</small></div>
    <div class="col-md-4"><label class="form-label fw-bold">Status</label><select class="form-select" name="status"><?php foreach(['rascunho','publicado','arquivado'] as $v):?><option value="<?=$v?>" <?=$post['status']===$v?'selected':''?>><?=e($v)?></option><?php endforeach?></select></div>
    <div class="col-md-6"><label class="form-label fw-bold">Turma</label><select class="form-select" name="turma_id"><option value="">Sem turma específica</option><?php foreach($turmas as $t):?><option value="<?=$t['id']?>" <?=((int)$post['turma_id']===(int)$t['id'])?'selected':''?>><?=e($t['nome'])?></option><?php endforeach?></select></div>
    <div class="col-md-6"><label class="form-label fw-bold">Aluno</label><select class="form-select" name="aluno_id"><option value="">Sem aluno específico</option><?php foreach($alunos as $a):?><option value="<?=$a['id']?>" <?=((int)$post['aluno_id']===(int)$a['id'])?'selected':''?>><?=e($a['nome'])?></option><?php endforeach?></select></div>
    <div class="col-12"><label class="form-label fw-bold">Conteúdo</label><textarea class="form-control" name="conteudo" rows="7" required><?=e($post['conteudo'])?></textarea></div>
    <div class="col-md-4"><label class="form-label fw-bold">Data do evento</label><input type="date" class="form-control" name="data_evento" value="<?=e($post['data_evento'])?>"></div>
    <div class="col-md-4"><label class="form-label fw-bold">Hora</label><input type="time" class="form-control" name="hora_evento" value="<?=e($post['hora_evento'] ? substr((string)$post['hora_evento'],0,5) : '')?>"></div>
    <div class="col-md-4"><label class="form-label fw-bold">Local</label><input class="form-control" name="local" value="<?=e($post['local'])?>" maxlength="190"></div>
    <div class="col-12"><label class="form-label fw-bold">Imagem</label><input type="file" class="form-control" name="imagem" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><?php if($post['imagem_url']):?><small class="text-muted">Imagem atual mantida se nenhuma nova for enviada.</small><?php endif?></div>
    <div class="col-12 portal-checks"><label><input type="checkbox" name="importante" <?=$post['importante']?'checked':''?>> Importante</label><label><input type="checkbox" name="exige_ciencia" <?=$post['exige_ciencia']?'checked':''?>> Exige ciência</label><label><input type="checkbox" name="fixado" <?=$post['fixado']?'checked':''?>> Fixado</label></div>
  </div>
  <button class="btn-scan mt-4" type="submit">Salvar publicação</button>
</form>
<?php if ($id): ?>
<form method="post" action="<?=e(url('admin/post-delete.php'))?>" class="mt-3 js-confirm-delete">
  <input type="hidden" name="csrf" value="<?=e(csrf())?>">
  <input type="hidden" name="id" value="<?=(int)$post['id']?>">
  <button class="btn-scan btn-delete-post" type="submit">Excluir publicação</button>
</form>
<?php endif; ?>
</section>
<script nonce="<?=e(csp_nonce())?>">document.querySelectorAll('.js-confirm-delete').forEach(form=>form.addEventListener('submit',event=>{if(!confirm('Excluir esta publicação? Esta ação não pode ser desfeita.'))event.preventDefault()}));</script>
<?php layout_footer();
