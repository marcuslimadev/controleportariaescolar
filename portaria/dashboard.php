<?php
require_once __DIR__ . '/../includes/helpers.php';
initSecureSession();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portaria - SCP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
<h1 class="h3 mb-4">Painel da Portaria</h1>
<div class="d-grid gap-3">
<a class="btn btn-primary btn-lg" href="/portaria/qrcode.php">Ler QR Code</a>
<a class="btn btn-success btn-lg" href="/portaria/cadastro_rapido.php">Cadastro rápido</a>
<a class="btn btn-outline-secondary btn-lg" href="/auth/logout.php">Sair</a>
</div>
</div>
</body>
</html>
