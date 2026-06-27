<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("Somente CLI\n");

require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/PasswordService.php';

use App\Support\PasswordService;

$pdo = db();
$password = 'Demo@2026!';
$hash = PasswordService::hash($password);
$lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer porta, sapien vitae cursus gravida, neque massa facilisis nibh, vitae luctus justo lorem sed sem. Donec sed posuere arcu. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae.';

function upsertUser(PDO $pdo, string $name, string $email, string $profile, string $hash): int {
    $query = $pdo->prepare(
        "INSERT INTO scp_usuarios(nome,email,senha_hash,perfil,ativo,deleted_at)
         VALUES(?,?,?,?,1,NULL)
         ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),nome=VALUES(nome),senha_hash=VALUES(senha_hash),perfil=VALUES(perfil),ativo=1,deleted_at=NULL"
    );
    $query->execute([$name, $email, $hash, $profile]);
    return (int)$pdo->lastInsertId();
}

function upsertClass(PDO $pdo, string $name, string $shift): int {
    $query = $pdo->prepare(
        "INSERT INTO scp_turmas(nome,turno,ativo) VALUES(?,?,1)
         ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),turno=VALUES(turno),ativo=1"
    );
    $query->execute([$name, $shift]);
    return (int)$pdo->lastInsertId();
}

function upsertGuardian(PDO $pdo, array $data, string $hash): int {
    $query = $pdo->prepare(
        "INSERT INTO scp_responsaveis(nome,cpf,email,telefone,foto,senha_hash,qr_token,ativo,deleted_at)
         VALUES(?,?,?,?,?,?,?,1,NULL)
         ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),nome=VALUES(nome),email=VALUES(email),telefone=VALUES(telefone),foto=VALUES(foto),senha_hash=VALUES(senha_hash),qr_token=COALESCE(qr_token,VALUES(qr_token)),ativo=1,deleted_at=NULL"
    );
    $query->execute([$data['nome'], $data['cpf'], $data['email'], $data['telefone'], $data['foto'], $hash, $data['qr_token']]);
    return (int)$pdo->lastInsertId();
}

function upsertStudent(PDO $pdo, array $data): int {
    $query = $pdo->prepare(
        "INSERT INTO scp_alunos(nome,cpf,data_nascimento,turma_id,foto,qr_token,ativo,deleted_at)
         VALUES(?,?,?,?,?,?,1,NULL)
         ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),nome=VALUES(nome),data_nascimento=VALUES(data_nascimento),turma_id=VALUES(turma_id),foto=VALUES(foto),ativo=1,deleted_at=NULL"
    );
    $query->execute([$data['nome'], $data['cpf'], $data['data_nascimento'], $data['turma_id'], $data['foto'], $data['qr_token']]);
    return (int)$pdo->lastInsertId();
}

function ensureTeacher(PDO $pdo, ?int $userId, string $name, string $email, string $phone): int {
    $query = $pdo->prepare('SELECT id FROM scp_professores WHERE email=? LIMIT 1');
    $query->execute([$email]);
    $id = (int)$query->fetchColumn();
    if ($id > 0) {
        $update = $pdo->prepare('UPDATE scp_professores SET usuario_id=?,nome=?,telefone=?,ativo=1 WHERE id=?');
        $update->execute([$userId, $name, $phone, $id]);
        return $id;
    }
    $insert = $pdo->prepare('INSERT INTO scp_professores(usuario_id,nome,email,telefone,ativo) VALUES(?,?,?,?,1)');
    $insert->execute([$userId, $name, $email, $phone]);
    return (int)$pdo->lastInsertId();
}

function linkTeacherClass(PDO $pdo, int $teacherId, int $classId): void {
    $query = $pdo->prepare('INSERT IGNORE INTO scp_professor_turma(professor_id,turma_id) VALUES(?,?)');
    $query->execute([$teacherId, $classId]);
}

function linkGuardian(PDO $pdo, int $studentId, int $guardianId, string $relationship): void {
    $query = $pdo->prepare(
        'INSERT INTO scp_aluno_responsavel(aluno_id,responsavel_id,parentesco,autoriza_consulta,autoriza_retirada)
         VALUES(?,?,?,1,1)
         ON DUPLICATE KEY UPDATE parentesco=VALUES(parentesco),autoriza_consulta=1,autoriza_retirada=1'
    );
    $query->execute([$studentId, $guardianId, $relationship]);
}

function upsertPost(PDO $pdo, array $data): int {
    $query = $pdo->prepare('SELECT id FROM scp_posts WHERE titulo=? LIMIT 1');
    $query->execute([$data['titulo']]);
    $id = (int)$query->fetchColumn();
    if ($id > 0) {
        $update = $pdo->prepare(
            "UPDATE scp_posts SET autor_id=?,tipo=?,conteudo=?,imagem_url=?,publico=?,turma_id=?,aluno_id=?,data_evento=?,hora_evento=?,local=?,importante=?,exige_ciencia=?,fixado=?,status='publicado',publicado_em=?,deleted_at=NULL WHERE id=?"
        );
        $update->execute([
            $data['autor_id'], $data['tipo'], $data['conteudo'], $data['imagem_url'], $data['publico'], $data['turma_id'], $data['aluno_id'],
            $data['data_evento'], $data['hora_evento'], $data['local'], $data['importante'], $data['exige_ciencia'], $data['fixado'], $data['publicado_em'], $id,
        ]);
        return $id;
    }
    $insert = $pdo->prepare(
        "INSERT INTO scp_posts(autor_id,tipo,titulo,conteudo,imagem_url,publico,turma_id,aluno_id,data_evento,hora_evento,local,importante,exige_ciencia,fixado,status,publicado_em)
         VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'publicado', ?)"
    );
    $insert->execute([
        $data['autor_id'], $data['tipo'], $data['titulo'], $data['conteudo'], $data['imagem_url'], $data['publico'], $data['turma_id'], $data['aluno_id'],
        $data['data_evento'], $data['hora_evento'], $data['local'], $data['importante'], $data['exige_ciencia'], $data['fixado'], $data['publicado_em'],
    ]);
    return (int)$pdo->lastInsertId();
}

$pdo->beginTransaction();
try {
    $users = [
        'admin' => upsertUser($pdo, 'Demo Admin Lorem', 'admin.demo@portaaberta.local', 'admin', $hash),
        'secretaria' => upsertUser($pdo, 'Demo Secretaria Ipsum', 'secretaria.demo@portaaberta.local', 'secretaria', $hash),
        'portaria' => upsertUser($pdo, 'Demo Portaria Dolor', 'portaria.demo@portaaberta.local', 'portaria', $hash),
        'professor' => upsertUser($pdo, 'Demo Professor Amet', 'professor.demo@portaaberta.local', 'professor', $hash),
    ];

    $classes = [
        'maternal' => upsertClass($pdo, 'Lorem Maternal', 'manha'),
        'fundamental' => upsertClass($pdo, 'Ipsum Fundamental', 'tarde'),
        'english' => upsertClass($pdo, 'English Demo Class', 'integral'),
    ];

    $teacherId = ensureTeacher($pdo, $users['professor'], 'Prof. Lorem McIpsum', 'teacher.demo@portaaberta.local', '11988887777');
    foreach ($classes as $classId) linkTeacherClass($pdo, $teacherId, $classId);

    $guardians = [
        upsertGuardian($pdo, ['nome'=>'Responsável Lorem Silva','cpf'=>'90000000001','email'=>'guardian.lorem@portaaberta.local','telefone'=>'11900000001','foto'=>null,'qr_token'=>bin2hex(random_bytes(32))], $hash),
        upsertGuardian($pdo, ['nome'=>'Guardian Ipsum Johnson','cpf'=>'90000000002','email'=>'guardian.ipsum@portaaberta.local','telefone'=>'11900000002','foto'=>null,'qr_token'=>bin2hex(random_bytes(32))], $hash),
        upsertGuardian($pdo, ['nome'=>'Responsável Dolor Santos','cpf'=>'90000000003','email'=>'guardian.dolor@portaaberta.local','telefone'=>'11900000003','foto'=>null,'qr_token'=>bin2hex(random_bytes(32))], $hash),
    ];

    $students = [
        upsertStudent($pdo, ['nome'=>'Aluno Lorem Silva','cpf'=>'91000000001','data_nascimento'=>'2018-03-12','turma_id'=>$classes['maternal'],'foto'=>null,'qr_token'=>bin2hex(random_bytes(32))]),
        upsertStudent($pdo, ['nome'=>'Student Ipsum Johnson','cpf'=>'91000000002','data_nascimento'=>'2016-08-21','turma_id'=>$classes['english'],'foto'=>null,'qr_token'=>bin2hex(random_bytes(32))]),
        upsertStudent($pdo, ['nome'=>'Aluna Dolor Santos','cpf'=>'91000000003','data_nascimento'=>'2015-11-04','turma_id'=>$classes['fundamental'],'foto'=>null,'qr_token'=>bin2hex(random_bytes(32))]),
        upsertStudent($pdo, ['nome'=>'Pupil Sit Amet','cpf'=>'91000000004','data_nascimento'=>'2017-06-30','turma_id'=>$classes['english'],'foto'=>null,'qr_token'=>bin2hex(random_bytes(32))]),
    ];

    linkGuardian($pdo, $students[0], $guardians[0], 'Responsável');
    linkGuardian($pdo, $students[1], $guardians[1], 'Guardian');
    linkGuardian($pdo, $students[2], $guardians[2], 'Responsável');
    linkGuardian($pdo, $students[3], $guardians[1], 'Guardian');

    $postIds = [];
    $postIds[] = upsertPost($pdo, [
        'autor_id'=>$users['secretaria'],'tipo'=>'comunicado','titulo'=>'[Demo PT] Comunicado Lorem Ipsum','conteudo'=>$lorem."\n\nSed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",'imagem_url'=>null,'publico'=>'toda_escola','turma_id'=>null,'aluno_id'=>null,'data_evento'=>null,'hora_evento'=>null,'local'=>null,'importante'=>1,'exige_ciencia'=>1,'fixado'=>1,'publicado_em'=>date('Y-m-d H:i:s', strtotime('-2 hours')),
    ]);
    $postIds[] = upsertPost($pdo, [
        'autor_id'=>$users['secretaria'],'tipo'=>'evento','titulo'=>'[Demo PT] Evento Dolor Sit Amet','conteudo'=>$lorem,'imagem_url'=>null,'publico'=>'toda_escola','turma_id'=>null,'aluno_id'=>null,'data_evento'=>date('Y-m-d', strtotime('+7 days')),'hora_evento'=>'09:30:00','local'=>'Auditório Lorem','importante'=>0,'exige_ciencia'=>0,'fixado'=>0,'publicado_em'=>date('Y-m-d H:i:s', strtotime('-1 hour')),
    ]);
    $postIds[] = upsertPost($pdo, [
        'autor_id'=>$users['professor'],'tipo'=>'atividade','titulo'=>'[Demo PT] Atividade da Turma Ipsum','conteudo'=>$lorem,'imagem_url'=>null,'publico'=>'turma','turma_id'=>$classes['fundamental'],'aluno_id'=>null,'data_evento'=>null,'hora_evento'=>null,'local'=>null,'importante'=>0,'exige_ciencia'=>0,'fixado'=>0,'publicado_em'=>date('Y-m-d H:i:s', strtotime('-45 minutes')),
    ]);
    $postIds[] = upsertPost($pdo, [
        'autor_id'=>$users['secretaria'],'tipo'=>'comunicado','titulo'=>'[EN Demo] Lorem Ipsum School Notice','conteudo'=>'Lorem ipsum dolor sit amet, consectetur adipiscing elit. This English demo post validates the bilingual public portal, timeline, and school communication flow. Families can read official notices, events, and reminders in English.','imagem_url'=>null,'publico'=>'toda_escola','turma_id'=>null,'aluno_id'=>null,'data_evento'=>null,'hora_evento'=>null,'local'=>null,'importante'=>1,'exige_ciencia'=>0,'fixado'=>0,'publicado_em'=>date('Y-m-d H:i:s', strtotime('-30 minutes')),
    ]);
    $postIds[] = upsertPost($pdo, [
        'autor_id'=>$users['secretaria'],'tipo'=>'evento','titulo'=>'[EN Demo] Open Gate Family Meeting','conteudo'=>'A bilingual demo event for families, teachers, and gatehouse staff. Lorem ipsum content is used to populate the schedule safely.','imagem_url'=>null,'publico'=>'toda_escola','turma_id'=>null,'aluno_id'=>null,'data_evento'=>date('Y-m-d', strtotime('+14 days')),'hora_evento'=>'14:00:00','local'=>'English Room','importante'=>0,'exige_ciencia'=>0,'fixado'=>0,'publicado_em'=>date('Y-m-d H:i:s', strtotime('-20 minutes')),
    ]);

    $like = $pdo->prepare('INSERT IGNORE INTO scp_post_curtidas(post_id,responsavel_id) VALUES(?,?)');
    $science = $pdo->prepare('INSERT IGNORE INTO scp_post_ciencias(post_id,responsavel_id,ip,user_agent) VALUES(?,?,?,?)');
    foreach ($postIds as $postId) {
        $like->execute([$postId, $guardians[0]]);
        $science->execute([$postId, $guardians[0], '127.0.0.1', 'Demo Seeder']);
    }

    $absence = $pdo->prepare(
        'INSERT INTO scp_avisos_falta(aluno_id,responsavel_id,turma_id,data_falta,motivo,observacao,status)
         SELECT ?,?,?,?,?,?,? FROM DUAL
         WHERE NOT EXISTS (SELECT 1 FROM scp_avisos_falta WHERE aluno_id=? AND data_falta=? AND motivo=?)'
    );
    $absence->execute([$students[0], $guardians[0], $classes['maternal'], date('Y-m-d', strtotime('+1 day')), 'Consulta médica', $lorem, 'enviado', $students[0], date('Y-m-d', strtotime('+1 day')), 'Consulta médica']);
    $absence->execute([$students[1], $guardians[1], $classes['english'], date('Y-m-d', strtotime('+2 days')), 'Family commitment', 'Lorem ipsum absence note in English for demo validation.', 'visualizado', $students[1], date('Y-m-d', strtotime('+2 days')), 'Family commitment']);

    $countAccess = $pdo->query("SELECT COUNT(*) FROM scp_registros_acesso WHERE origem='demo-lorem'")->fetchColumn();
    if ((int)$countAccess === 0) {
        $access = $pdo->prepare('INSERT INTO scp_registros_acesso(aluno_id,responsavel_id,tipo,registrado_em,usuario_id,origem,observacao,ip) VALUES(?,?,?,?,?,?,?,?)');
        foreach ($students as $index => $studentId) {
            $guardianId = $guardians[min($index, count($guardians)-1)];
            $access->execute([$studentId, $guardianId, 'entrada', date('Y-m-d 07:4' . $index . ':00'), $users['portaria'], 'demo-lorem', 'Lorem ipsum entrada demo', '127.0.0.1']);
            $access->execute([$studentId, $guardianId, 'saida', date('Y-m-d 17:1' . $index . ':00'), $users['portaria'], 'demo-lorem', 'Lorem ipsum saída demo', '127.0.0.1']);
        }
    }

    $alert = $pdo->prepare(
        "INSERT INTO scp_alertas_cracha(aluno_id,qr_token,nome_informante,telefone_informante,mensagem,ip,user_agent,status)
         SELECT id,qr_token,'Demo Lorem','11900009999','Lorem ipsum emergency alert demo.','127.0.0.1','Demo Seeder','novo'
         FROM scp_alunos
         WHERE id=?
           AND NOT EXISTS (SELECT 1 FROM scp_alertas_cracha WHERE aluno_id=? AND user_agent='Demo Seeder')"
    );
    $alert->execute([$students[0], $students[0]]);

    $badge = $pdo->prepare(
        "INSERT INTO scp_crachas_responsavel_emitidos(responsavel_id,emitido_por,token_no_momento)
         SELECT id,?,qr_token FROM scp_responsaveis
         WHERE id=? AND qr_token IS NOT NULL
           AND NOT EXISTS (SELECT 1 FROM scp_crachas_responsavel_emitidos WHERE responsavel_id=? AND token_no_momento=scp_responsaveis.qr_token)"
    );
    foreach ($guardians as $guardianId) {
        $badge->execute([$users['admin'], $guardianId, $guardianId]);
    }

    $pdo->commit();

    echo "Seed demo concluído.\n";
    echo "Senha padrão das contas demo: {$password}\n";
    echo "Usuários: admin.demo@portaaberta.local, secretaria.demo@portaaberta.local, portaria.demo@portaaberta.local, professor.demo@portaaberta.local\n";
    echo "Responsáveis: guardian.lorem@portaaberta.local / CPF 90000000001, guardian.ipsum@portaaberta.local / CPF 90000000002\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
