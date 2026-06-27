<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('post.manage');
$postService = new \App\Services\PostService(
    new \App\Infrastructure\Persistence\PdoPostRepository(db()),
    new \App\Infrastructure\Logging\DatabaseAuditLogger(),
);
$posts = $postService->listAdminPosts();
layout_header('Publicações');
?>
<div class="page-heading"><div><span class="gate-eyebrow">COMUNICAÇÃO</span><h1>Publicações</h1><p>Comunicados, eventos, alertas e lembretes oficiais.</p></div><div class="page-actions"><a class="btn btn-primary" href="<?=e(url('admin/post-form.php'))?>">Nova publicação</a></div></div>
<?php if ($posts): ?>
<div class="data-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Título</th><th>Tipo</th><th>Público</th><th>Status</th><th>Autor</th><th></th></tr></thead><tbody>
<?php foreach ($posts as $post): ?>
<tr><td><strong><?=e($post['titulo'])?></strong><br><small><?=e($post['turma'] ?: $post['aluno'] ?: 'Toda escola/equipe')?></small></td><td><?=e($post['tipo'])?></td><td><?=e($post['publico'])?></td><td><span class="status-pill <?=$post['status']==='publicado'?'active':'inactive'?>"><?=e($post['status'])?></span></td><td><?=e($post['autor'])?></td><td class="text-end"><div class="post-actions"><a class="btn btn-sm btn-outline-primary" href="<?=e(url('admin/post-form.php?id='.(int)$post['id']))?>">Editar</a><form method="post" action="<?=e(url('admin/post-delete.php'))?>" onsubmit="return confirm('Excluir esta publicação? Esta ação não pode ser desfeita.');"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="id" value="<?=(int)$post['id']?>"><button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button></form></div></td></tr>
<?php endforeach ?>
</tbody></table></div></div>
<?php else: ?><div class="empty-state">Nenhuma publicação cadastrada.</div><?php endif ?>
<?php layout_footer();
