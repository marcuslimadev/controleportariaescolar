<?php
require __DIR__.'/../../includes/bootstrap.php';
header('Content-Type: application/json');
require_role(['admin','secretaria','portaria']);
verify_csrf();

$token=extract_qr_token((string)($_POST['token']??''));
$items=json_decode((string)($_POST['items']??'[]'),true);
if(!is_array($items)||!$items){http_response_code(422);echo json_encode(['message'=>'Nenhuma criança selecionada.']);exit;}

$q=db()->prepare('SELECT id FROM scp_responsaveis WHERE qr_token=? AND ativo=1');
$q->execute([$token]);
$responsavelId=(int)$q->fetchColumn();
if(!$responsavelId){http_response_code(404);echo json_encode(['message'=>'Responsável não encontrado.']);exit;}

$insert=db()->prepare('INSERT INTO scp_registros_acesso(aluno_id,responsavel_id,tipo,usuario_id,origem,observacao,correcao_manual,ip) VALUES(?,?,?,?,?,?,?,?)');
$check=db()->prepare('SELECT COUNT(*) FROM scp_aluno_responsavel WHERE aluno_id=? AND responsavel_id=? AND autoriza_retirada=1');
$registrados=[];
foreach($items as $item){
    $alunoId=(int)($item['aluno_id']??0);
    $tipo=(string)($item['tipo']??'');
    $manual=!empty($item['manual']);
    $obs=trim((string)($item['observacao']??''));
    if(!in_array($tipo,['entrada','saida'],true)||($manual&&strlen($obs)<5))continue;
    $check->execute([$alunoId,$responsavelId]);
    if(!$check->fetchColumn())continue;
    $insert->execute([$alunoId,$responsavelId,$tipo,$_SESSION['user_id'],substr($_SERVER['HTTP_USER_AGENT']??'',0,100),$obs?:null,$manual,$_SERVER['REMOTE_ADDR']??null]);
    audit($manual?'correcao_manual':'registrar_acesso','scp_alunos',$alunoId,['tipo'=>$tipo,'responsavel_id'=>$responsavelId]);
    $registrados[]=$tipo;
}
if(!$registrados){http_response_code(422);echo json_encode(['message'=>'Não foi possível registrar as crianças selecionadas.']);exit;}

$entradas=count(array_filter($registrados,fn($t)=>$t==='entrada'));
$saidas=count($registrados)-$entradas;
$parts=[];
if($entradas)$parts[]=$entradas.' entrada'.($entradas>1?'s':'');
if($saidas)$parts[]=$saidas.' saída'.($saidas>1?'s':'');
echo json_encode(['message'=>implode(' e ',$parts).' registrada'.(count($registrados)>1?'s':'').' com sucesso.']);
