<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('post.manage');
$postService = \App\Support\ServiceFactory::posts();
$filters = [
    'q' => (string)($_GET['q'] ?? ''),
    'status' => (string)($_GET['status'] ?? ''),
    'publico' => (string)($_GET['publico'] ?? ''),
    'tipo' => (string)($_GET['tipo'] ?? ''),
];
$posts = $postService->listAdminPosts($filters);
layout_header('Publicações');
?>
<div class="page-heading"><div><span class="gate-eyebrow">COMUNICAÇÃO</span><h1>Publicações</h1><p>Comunicados, eventos, alertas e lembretes oficiais.</p></div><div class="page-actions"><a class="btn btn-primary" href="<?=e(url('admin/post-form.php'))?>">Nova publicação</a></div></div>
<form class="section-card post-filter-form">
  <div>
    <label class="form-label fw-bold" for="q">Buscar</label>
    <input id="q" class="form-control" name="q" value="<?=e($filters['q'])?>" placeholder="Título, conteúdo ou autor">
  </div>
  <div>
    <label class="form-label fw-bold" for="status">Status</label>
    <select id="status" class="form-select" name="status">
      <option value="">Todos</option>
      <?php foreach(['rascunho'=>'Rascunho','publicado'=>'Publicado','arquivado'=>'Arquivado'] as $value=>$label): ?><option value="<?=e($value)?>" <?=$filters['status']===$value?'selected':''?>><?=e($label)?></option><?php endforeach ?>
    </select>
  </div>
  <div>
    <label class="form-label fw-bold" for="publico">Público</label>
    <select id="publico" class="form-select" name="publico">
      <option value="">Todos</option>
      <?php foreach(['publico'=>'Feed público','toda_escola'=>'Privado - todos','turma'=>'Privado - turma','aluno'=>'Privado - aluno','equipe'=>'Privado - equipe'] as $value=>$label): ?><option value="<?=e($value)?>" <?=$filters['publico']===$value?'selected':''?>><?=e($label)?></option><?php endforeach ?>
    </select>
  </div>
  <div>
    <label class="form-label fw-bold" for="tipo">Tipo</label>
    <select id="tipo" class="form-select" name="tipo">
      <option value="">Todos</option>
      <?php foreach(['comunicado','atividade','evento','programação','alerta','cardápio','lembrete'] as $value): ?><option value="<?=e($value)?>" <?=$filters['tipo']===$value?'selected':''?>><?=e(ucfirst($value))?></option><?php endforeach ?>
    </select>
  </div>
  <div class="post-filter-actions">
    <button class="btn btn-primary">Filtrar</button>
    <a class="btn btn-outline-primary" href="<?=e(url('admin/posts.php'))?>">Limpar</a>
  </div>
</form>
<?php if ($posts): ?>
<div class="data-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Título</th><th>Tipo</th><th>Público</th><th>Status</th><th>Autor</th><th>Engajamento</th><th></th></tr></thead><tbody>
<?php foreach ($posts as $post): ?>
<?php $scopeLabels=['publico'=>'Feed público','toda_escola'=>'Privado - todos','turma'=>'Privado - turma','aluno'=>'Privado - aluno','equipe'=>'Privado - equipe']; ?>
<tr><td><strong><?=e($post['titulo'])?></strong><?php if($post['anexo_url']):?> <span class="pin-badge">Anexo</span><?php endif?><br><small><?=e($post['turma'] ?: $post['aluno'] ?: ($post['publico']==='publico' ? 'Página inicial pública' : 'Todos os usuários/equipe'))?></small></td><td><?=e($post['tipo'])?></td><td><?=e($scopeLabels[$post['publico']] ?? $post['publico'])?></td><td><span class="status-pill <?=$post['status']==='publicado'?'active':'inactive'?>"><?=e($post['status'])?></span></td><td><?=e($post['autor'])?></td><td><div class="post-insights"><?php if($post['exige_ciencia']):?><a class="status-pill active" href="<?=e(url('admin/post-ciencias.php?id='.(int)$post['id']))?>"><?=(int)($post['ciencia_total'] ?? 0)?> ciência(s)</a><?php if(!empty($post['ciencia_ultima'])):?><small>Última: <?=e(format_br_datetime($post['ciencia_ultima']))?></small><?php endif ?><?php else:?><span class="text-muted">Não exige ciência</span><?php endif?><span><?=(int)($post['comentarios_total'] ?? 0)?> comentário(s)</span><?php if((int)($post['comentarios_pendentes'] ?? 0)>0):?><a class="pin-badge" href="<?=e(url('admin/comentarios.php'))?>"><?=(int)$post['comentarios_pendentes']?> pendente(s)</a><?php endif?></div></td><td class="text-end"><div class="post-actions"><a class="btn btn-sm btn-outline-primary" href="<?=e(url('admin/post-form.php?id='.(int)$post['id']))?>">Editar</a><form method="post" action="<?=e(url('admin/post-delete.php'))?>" class="js-confirm-delete"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="id" value="<?=(int)$post['id']?>"><button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button></form></div></td></tr>
<?php endforeach ?>
</tbody></table></div></div>
<?php else: ?><div class="empty-state">Nenhuma publicação cadastrada.</div><?php endif ?>
<script nonce="<?=e(csp_nonce())?>">document.querySelectorAll('.js-confirm-delete').forEach(form=>form.addEventListener('submit',event=>{if(!confirm('Excluir esta publicação? Esta ação não pode ser desfeita.'))event.preventDefault()}));</script>
<?php layout_footer();
