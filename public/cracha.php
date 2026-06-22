<?php
require __DIR__.'/../includes/bootstrap.php';
$qrToken=(string)($_GET['token']??'');$inviteToken=(string)($_GET['convite']??'');$alunoId=(int)($_GET['aluno']??0);$student=null;
if($qrToken!==''){$q=db()->prepare('SELECT a.*,t.nome turma FROM scp_alunos a LEFT JOIN scp_turmas t ON t.id=a.turma_id WHERE a.qr_token=? AND a.ativo=1');$q->execute([$qrToken]);$student=$q->fetch();}
elseif($inviteToken!==''&&$alunoId){$q=db()->prepare("SELECT a.*,t.nome turma FROM scp_convites_cadastro c JOIN scp_alunos a ON a.id=c.aluno_id LEFT JOIN scp_turmas t ON t.id=a.turma_id WHERE c.token_hash=? AND c.aluno_id=? AND c.status='aprovado'");$q->execute([hash('sha256',$inviteToken),$alunoId]);$student=$q->fetch();}
if(!$student){http_response_code(404);layout_header('Crachá não encontrado');echo '<div class="alert alert-warning mx-auto" style="max-width:520px">Crachá inválido ou ainda não aprovado.</div>';layout_footer();exit;}
$emergencyUrl=url('c/'.$student['qr_token']);
layout_header('Crachá digital');
?>
<section class="public-badge"><div class="text-center mb-3"><span class="gate-eyebrow">CRACHÁ DIGITAL</span><h1><?=e($student['nome'])?></h1><p>Apresente esta tela na entrada e na saída.</p></div><article class="mobile-credential"><?php if($student['foto']):?><img class="credential-photo" src="<?=e($student['foto'])?>" alt="Foto de <?=e($student['nome'])?>"><?php endif?><h2><?=e($student['nome'])?></h2><span><?=e($student['turma']??'Sem turma')?></span><div id="public-qrcode"></div><strong>SCP Escolar</strong></article><div class="badge-actions"><button class="btn-scan" type="button" onclick="window.print()">Imprimir ou salvar em PDF</button><a class="btn btn-outline-primary btn-lg" href="<?=e(url('login.php'))?>">Entrar no portal da família</a></div><p class="privacy-note">Não compartilhe este link publicamente. Ele funciona como identificação digital.</p></section>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script><script>new QRCode(document.querySelector('#public-qrcode'),{text:<?=json_encode($emergencyUrl)?>,width:250,height:250,correctLevel:QRCode.CorrectLevel.H});</script>
<?php layout_footer();
