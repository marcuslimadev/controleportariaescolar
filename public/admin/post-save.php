<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_role(['admin','secretaria']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/posts.php');
verify_csrf();

try {
    $id = (int)($_POST['id'] ?? 0);
    $allowedTipos = ['comunicado','atividade','evento','programação','alerta','cardápio','lembrete'];
    $allowedPublicos = ['toda_escola','turma','aluno','equipe'];
    $allowedStatus = ['rascunho','publicado','arquivado'];
    $tipo = in_array($_POST['tipo'] ?? '', $allowedTipos, true) ? $_POST['tipo'] : 'comunicado';
    $publico = in_array($_POST['publico'] ?? '', $allowedPublicos, true) ? $_POST['publico'] : 'toda_escola';
    $status = in_array($_POST['status'] ?? '', $allowedStatus, true) ? $_POST['status'] : 'rascunho';
    $titulo = trim((string)($_POST['titulo'] ?? ''));
    $conteudo = trim((string)($_POST['conteudo'] ?? ''));
    if ($titulo === '' || $conteudo === '') throw new RuntimeException('Informe título e conteúdo.');
    $turmaId = ($_POST['turma_id'] ?? '') !== '' ? (int)$_POST['turma_id'] : null;
    $alunoId = ($_POST['aluno_id'] ?? '') !== '' ? (int)$_POST['aluno_id'] : null;
    if ($publico === 'turma' && !$turmaId) throw new RuntimeException('Selecione a turma.');
    if ($publico === 'aluno' && !$alunoId) throw new RuntimeException('Selecione o aluno.');
    $imagemUrl = null;
    if (!empty($_FILES['imagem'])) $imagemUrl = save_portal_upload($_FILES['imagem'], 'posts', 'image');

    if ($id) {
        $q = db()->prepare('SELECT p.*, u.perfil autor_perfil FROM scp_posts p JOIN scp_usuarios u ON u.id=p.autor_id WHERE p.id=? AND p.deleted_at IS NULL');
        $q->execute([$id]);
        $current = $q->fetch();
        if (!$current) throw new RuntimeException('Publicação não encontrada.');
        if (($_SESSION['role'] ?? '') === 'secretaria' && (int)$current['autor_id'] !== (int)$_SESSION['user_id'] && $current['autor_perfil'] !== 'secretaria') throw new RuntimeException('Você só pode editar publicações da secretaria ou criadas por você.');
        $imagemUrl = $imagemUrl ?: $current['imagem_url'];
        $publishedAt = $status === 'publicado' ? ($current['publicado_em'] ?: date('Y-m-d H:i:s')) : null;
        $q = db()->prepare('UPDATE scp_posts SET tipo=?,titulo=?,conteudo=?,imagem_url=?,publico=?,turma_id=?,aluno_id=?,data_evento=?,hora_evento=?,local=?,importante=?,exige_ciencia=?,fixado=?,status=?,publicado_em=? WHERE id=?');
        $q->execute([$tipo,$titulo,$conteudo,$imagemUrl,$publico,$turmaId,$alunoId,$_POST['data_evento'] ?: null,$_POST['hora_evento'] ?: null,trim((string)$_POST['local']) ?: null,isset($_POST['importante']),isset($_POST['exige_ciencia']),isset($_POST['fixado']),$status,$publishedAt,$id]);
        audit('editar_post', 'scp_posts', $id);
    } else {
        $publishedAt = $status === 'publicado' ? date('Y-m-d H:i:s') : null;
        $q = db()->prepare('INSERT INTO scp_posts(autor_id,tipo,titulo,conteudo,imagem_url,publico,turma_id,aluno_id,data_evento,hora_evento,local,importante,exige_ciencia,fixado,status,publicado_em) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute([$_SESSION['user_id'],$tipo,$titulo,$conteudo,$imagemUrl,$publico,$turmaId,$alunoId,$_POST['data_evento'] ?: null,$_POST['hora_evento'] ?: null,trim((string)$_POST['local']) ?: null,isset($_POST['importante']),isset($_POST['exige_ciencia']),isset($_POST['fixado']),$status,$publishedAt]);
        $id = (int)db()->lastInsertId();
        audit('criar_post', 'scp_posts', $id);
    }
    flash('Publicação salva.');
} catch (Throwable $e) {
    flash('Não foi possível salvar: '.$e->getMessage(), 'danger');
}
redirect('admin/posts.php');
