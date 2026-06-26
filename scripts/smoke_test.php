<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("Somente CLI\n");
require __DIR__ . '/../config/database.php';

const BASE = 'https://scp.lojadaesquina.store/';
function sessionRequest(string $jar, string $path, ?array $post=null): string {
    $ch=curl_init(BASE.$path);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_COOKIEJAR=>$jar,CURLOPT_COOKIEFILE=>$jar,CURLOPT_POST=>$post!==null,CURLOPT_POSTFIELDS=>$post?http_build_query($post):null,CURLOPT_TIMEOUT=>20]);
    $body=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
    if($body===false || $code>=400) throw new RuntimeException("HTTP $code em $path: ".curl_error($ch));
    return $body;
}
function login(string $tipo,string $usuario,string $senha,string $expected): array {
    $jar=tempnam(sys_get_temp_dir(),'scp_'); $page=sessionRequest($jar,'login.php');
    if(!preg_match('/name="csrf" value="([a-f0-9]+)"/',$page,$m)) throw new RuntimeException('CSRF não encontrado');
    $body=sessionRequest($jar,'login.php',['csrf'=>$m[1],'tipo'=>$tipo,'login'=>$usuario,'senha'=>$senha]);
    if(!str_contains($body,$expected)) throw new RuntimeException("Falha no login de $usuario: ".substr(trim(strip_tags($body)),0,180));
    return [$jar,$body];
}

[$adminJar] = login('equipe','admin@scp.local','SCP@2026!Acesso#7kP','Timeline oficial');
[$portariaJar,$portaria] = login('equipe','portaria@scp.local','Portaria@2026!Teste','Controle de acesso');
if(!preg_match('/const csrf="([a-f0-9]+)"/',$portaria,$m)) throw new RuntimeException('CSRF da portaria não encontrado');
$token=(string)db()->query("SELECT qr_token FROM scp_responsaveis WHERE cpf='11144477735'")->fetchColumn();
$lookup=json_decode(sessionRequest($portariaJar,'portaria/lookup.php',['csrf'=>$m[1],'token'=>$token]),true,512,JSON_THROW_ON_ERROR);
if(empty($lookup['ok']) || empty($lookup['responsavel']) || empty($lookup['children'][0])) throw new RuntimeException('Consulta do QR falhou');
$child=$lookup['children'][0];
$tipo=$child['sugerida'];
$items=json_encode([['aluno_id'=>$child['id'],'tipo'=>$tipo,'manual'=>false,'observacao'=>'']], JSON_THROW_ON_ERROR);
$registro=json_decode(sessionRequest($portariaJar,'portaria/registrar.php',['csrf'=>$m[1],'token'=>$token,'items'=>$items]),true,512,JSON_THROW_ON_ERROR);
if(!str_contains($registro['message']??'','sucesso')) throw new RuntimeException('Registro de acesso falhou');
[$parentJar,$portal] = login('responsavel','11144477735','Responsavel@2026!Teste','Timeline oficial');
if(!str_contains($portal,'Aluno Demonstração')) throw new RuntimeException('Aluno não apareceu no portal');
@unlink($adminJar);@unlink($portariaJar);@unlink($parentJar);
echo "ADMIN_OK PORTARIA_OK QR_OK REGISTRO_".strtoupper($tipo)." PARENT_OK\n";
