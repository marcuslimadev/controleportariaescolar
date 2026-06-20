<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/qrcode.php';

initSecureSession();

$pdo = getDBConnection();
$token = extractQRCodeToken($publicToken ?? ($_GET['token'] ?? ''));
$error = '';
$success = '';
$student = null;

if ($token === '') {
    http_response_code(404);
    $error = 'Crachá não identificado.';
} else {
    $student = getStudentByToken($pdo, $token);
    if (!$student) {
        http_response_code(404);
        $error = 'Crachá não encontrado ou inativo.';
    }
}

if ($student && isset($_SESSION['user_id']) && ($_SESSION['user_type'] ?? '') === 'portaria') {
    header('Location: /portaria/qrcode.php?token=' . urlencode($token));
    exit;
}

if ($student && isset($_SESSION['responsavel_id'])) {
    $stmt = $pdo->prepare('SELECT 1 FROM aluno_responsavel WHERE aluno_id = ? AND responsavel_id = ? AND autorizado_consulta = 1 LIMIT 1');
    $stmt->execute([$student['id'], $_SESSION['responsavel_id']]);
    if ($stmt->fetchColumn()) {
        header('Location: /responsavel/historico.php?aluno_id=' . intval($student['id']));
        exit;
    }
}

if ($student && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomeInformante = sanitizeInput($_POST['nome_informante'] ?? '');
    $telefoneInformante = sanitizeInput($_POST['telefone_informante'] ?? '');
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    $latitude = sanitizeInput($_POST['latitude'] ?? '');
    $longitude = sanitizeInput($_POST['longitude'] ?? '');

    $stmt = $pdo->prepare('INSERT INTO alertas_cracha (aluno_id, token_qrcode, nome_informante, telefone_informante, mensagem, latitude, longitude, ip_origem, dispositivo, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$student['id'], $token, $nomeInformante ?: null, $telefoneInformante ?: null, $mensagem ?: null, $latitude ?: null, $longitude ?: null, getClientIP(), getDeviceInfo()]);

    logAuditAction($pdo, null, 'ALERTA_CRACHA_PUBLICO', 'alertas_cracha', $pdo->lastInsertId(), null, ['aluno_id' => $student['id'], 'token_qrcode' => $token]);

    $success = 'Alerta registrado. A escola poderá consultar este aviso no sistema.';
}

$schoolPhone = defined('SCHOOL_PHONE') ? SCHOOL_PHONE : '';
$schoolWhatsApp = defined('SCHOOL_WHATSAPP') ? preg_replace('/\D+/', '', SCHOOL_WHATSAPP) : '';
$schoolName = defined('SCHOOL_NAME') ? SCHOOL_NAME : 'Escola cadastrada';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crachá de Segurança - SCP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .security-card { max-width: 560px; margin: 24px auto; border: 0; border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,.08); overflow: hidden; }
        .top { background: #0d6efd; color: white; padding: 28px 22px; text-align: center; }
        .icon { font-size: 54px; }
        .content { padding: 24px; }
        .btn-lg { padding: 14px 18px; font-weight: 700; }
    </style>
</head>
<body>
    <div class="container px-3">
        <div class="card security-card">
            <div class="top"><div class="icon"><i class="bi bi-shield-check"></i></div><h1 class="h3 mb-1">SCP</h1><p class="mb-0">Sistema de Controle de Portaria</p></div>
            <div class="content">
                <?php if ($error): ?>
                    <div class="alert alert-warning mb-0"><?php echo escapeHTML($error); ?></div>
                <?php else: ?>
                    <?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle"></i> <?php echo escapeHTML($success); ?></div><?php endif; ?>
                    <h2 class="h5">Crachá vinculado à <?php echo escapeHTML($schoolName); ?></h2>
                    <p class="text-muted">Este crachá pertence a uma criança cadastrada no sistema de segurança escolar.</p>
                    <p class="mb-3">Se você encontrou esta criança desacompanhada ou em situação de risco, envie um alerta para a escola. Nenhum dado pessoal da criança ou dos responsáveis é exibido publicamente.</p>
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="latitude" id="latitude"><input type="hidden" name="longitude" id="longitude">
                        <div class="mb-3"><label class="form-label">Seu nome, se desejar</label><input type="text" name="nome_informante" class="form-control" maxlength="150"></div>
                        <div class="mb-3"><label class="form-label">Seu telefone, se desejar</label><input type="tel" name="telefone_informante" class="form-control" maxlength="30"></div>
                        <div class="mb-3"><label class="form-label">Mensagem opcional</label><textarea name="mensagem" class="form-control" rows="3" maxlength="500" placeholder="Ex: encontrei a criança na entrada da escola, no ponto de ônibus, na praça..."></textarea></div>
                        <button type="submit" class="btn btn-danger btn-lg w-100"><i class="bi bi-exclamation-triangle"></i> Alertar escola</button>
                    </form>
                    <?php if ($schoolPhone): ?><a href="tel:<?php echo escapeHTML(preg_replace('/\D+/', '', $schoolPhone)); ?>" class="btn btn-outline-primary btn-lg w-100 mt-3"><i class="bi bi-telephone"></i> Ligar para a escola</a><?php endif; ?>
                    <?php if ($schoolWhatsApp): ?><a href="https://wa.me/<?php echo escapeHTML($schoolWhatsApp); ?>?text=<?php echo rawurlencode('Alerta: encontrei uma criança com crachá da escola. Token: ' . $token); ?>" class="btn btn-outline-success btn-lg w-100 mt-3"><i class="bi bi-whatsapp"></i> WhatsApp da escola</a><?php endif; ?>
                    <div class="alert alert-info mt-4 mb-0 small"><i class="bi bi-info-circle"></i> Para registrar entrada ou saída, o agente de portaria precisa estar logado no sistema.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                document.getElementById('latitude').value = position.coords.latitude;
                document.getElementById('longitude').value = position.coords.longitude;
            });
        }
    </script>
</body>
</html>
