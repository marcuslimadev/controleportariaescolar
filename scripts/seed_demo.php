<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("Somente CLI\n");
require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/PasswordService.php';

$pdo = db();
$pdo->beginTransaction();
try {
    $q = $pdo->prepare("INSERT INTO scp_usuarios(nome,email,senha_hash,perfil,ativo) VALUES(?,?,?,'portaria',1) ON DUPLICATE KEY UPDATE nome=VALUES(nome),senha_hash=VALUES(senha_hash),perfil='portaria',ativo=1");
    $q->execute(['Agente de Portaria', 'portaria@scp.local', \App\Support\PasswordService::hash('Portaria@2026!Teste')]);

    $turmaId = (int)$pdo->query("SELECT id FROM scp_turmas WHERE nome='Turma Demonstração' LIMIT 1")->fetchColumn();
    if (!$turmaId) { $pdo->exec("INSERT INTO scp_turmas(nome,turno,ativo) VALUES('Turma Demonstração','manha',1)"); $turmaId=(int)$pdo->lastInsertId(); }

    $q = $pdo->prepare("INSERT INTO scp_responsaveis(nome,cpf,email,telefone,senha_hash,ativo) VALUES(?,?,?,?,?,1) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),nome=VALUES(nome),email=VALUES(email),senha_hash=VALUES(senha_hash),ativo=1");
    $q->execute(['Responsável Teste','11144477735','responsavel@scp.local','11999990000',\App\Support\PasswordService::hash('Responsavel@2026!Teste')]);
    $responsavelId = (int)$pdo->lastInsertId();

    $q = $pdo->prepare("INSERT INTO scp_alunos(nome,cpf,data_nascimento,turma_id,qr_token,ativo) VALUES(?,?,?,?,?,1) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),nome=VALUES(nome),turma_id=VALUES(turma_id),ativo=1");
    $q->execute(['Aluno Demonstração','52998224725','2015-05-15',$turmaId,bin2hex(random_bytes(32))]);
    $alunoId = (int)$pdo->lastInsertId();

    $q = $pdo->prepare("INSERT INTO scp_aluno_responsavel(aluno_id,responsavel_id,parentesco,autoriza_consulta,autoriza_retirada) VALUES(?,?,'Responsável',1,1) ON DUPLICATE KEY UPDATE autoriza_consulta=1,autoriza_retirada=1");
    $q->execute([$alunoId,$responsavelId]);
    $pdo->commit();
    echo "Dados de demonstração criados.\n";
} catch (Throwable $e) {
    $pdo->rollBack(); throw $e;
}
