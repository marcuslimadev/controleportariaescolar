<?php
require_once __DIR__ . '/../includes/helpers.php';
initSecureSession();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - SCP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
<h1 class="h3 mb-4">Painel da Escola</h1>
<div class="row g-3">
<div class="col-md-3"><a class="btn btn-primary w-100 p-3" href="/admin/alunos.php">Alunos</a></div>
<div class="col-md-3"><a class="btn btn-primary w-100 p-3" href="/admin/responsaveis.php">Responsáveis</a></div>
<div class="col-md-3"><a class="btn btn-primary w-100 p-3" href="/admin/turmas.php">Turmas</a></div>
<div class="col-md-3"><a class="btn btn-success w-100 p-3" href="/portaria/dashboard.php">Portaria</a></div>
</div>
</div>
</body>
</html>
