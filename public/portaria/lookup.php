<?php
require __DIR__.'/../../includes/bootstrap.php';
header('Content-Type: application/json');
require_role(['admin','secretaria','portaria']);
if($_SERVER['REQUEST_METHOD']!=='POST')exit('{}');
verify_csrf();
$token=extract_qr_token((string)($_POST['token']??''));

$q=db()->prepare('SELECT id,nome,foto,qr_token FROM scp_responsaveis WHERE qr_token=? AND ativo=1');
$q->execute([$token]);
$responsavel=$q->fetch();

if(!$responsavel){
    $q=db()->prepare('SELECT id FROM scp_alunos WHERE qr_token=? AND ativo=1');
    $q->execute([$token]);
    if($q->fetchColumn()){echo json_encode(['ok'=>false,'message'=>'Este é o crachá de segurança da criança, não o crachá de retirada. Peça para o responsável apresentar o crachá dele.']);exit;}
    echo json_encode(['ok'=>false]);
    exit;
}

$q=db()->prepare('SELECT a.id,a.nome,a.foto,t.nome turma,(SELECT tipo FROM scp_registros_acesso r WHERE r.aluno_id=a.id ORDER BY r.registrado_em DESC,r.id DESC LIMIT 1) ultimo FROM scp_alunos a JOIN scp_aluno_responsavel ar ON ar.aluno_id=a.id LEFT JOIN scp_turmas t ON t.id=a.turma_id WHERE ar.responsavel_id=? AND ar.autoriza_retirada=1 AND a.ativo=1 ORDER BY a.nome');
$q->execute([$responsavel['id']]);
$children=array_map(function($a){
    $dentro=$a['ultimo']==='entrada';
    return ['id'=>(int)$a['id'],'nome'=>$a['nome'],'foto'=>$a['foto'],'turma'=>$a['turma'],'dentro'=>$dentro,'sugerida'=>$dentro?'saida':'entrada'];
},$q->fetchAll());

if(!$children){echo json_encode(['ok'=>false,'message'=>'Nenhuma criança autorizada para retirada está vinculada a este responsável.']);exit;}

echo json_encode(['ok'=>true,'responsavel'=>['id'=>(int)$responsavel['id'],'nome'=>$responsavel['nome'],'foto'=>$responsavel['foto'],'token'=>$responsavel['qr_token']],'children'=>$children]);
