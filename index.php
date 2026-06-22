<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';

initSecureSession();

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (preg_match('#^/c/([^/]+)$#', $requestPath, $matches)) {
    $publicToken = $matches[1];
    require __DIR__ . '/c.php';
    exit;
}

if (isset($_SESSION['user_id'])) {
    if (($_SESSION['user_type'] ?? '') === 'admin') {
        header('Location: /admin/dashboard.php');
        exit;
    }

    if (($_SESSION['user_type'] ?? '') === 'portaria') {
        header('Location: /portaria/dashboard.php');
        exit;
    }
}

if (isset($_SESSION['responsavel_id'])) {
    header('Location: /responsavel/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Portaria Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .box { max-width: 720px; text-align: center; color: white; padding: 30px; }
        .logo { font-size: 72px; margin-bottom: 20px; }
        .btn-custom { padding: 14px 32px; font-size: 18px; font-weight: 600; border-radius: 12px; margin: 8px; }
    </style>
</head>
<body>
    <main class="box">
        <div class="logo"><i class="bi bi-shield-check"></i></div>
        <h1>Controle de Portaria Escolar</h1>
        <p class="lead">Sistema de entrada, saída, crachá QR Code e modo emergência.</p>
        <a href="/auth/login.php" class="btn btn-light btn-custom">Administração / Portaria</a>
        <a href="/responsavel/login.php" class="btn btn-outline-light btn-custom">Portal dos Responsáveis</a>
    </main>
</body>
</html>
