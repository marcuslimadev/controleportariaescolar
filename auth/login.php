<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

initSecureSession();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $pdo = getDBConnection();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ? AND status = "ativo" LIMIT 1');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_name'] = $usuario['nome'];
        $_SESSION['user_type'] = $usuario['tipo_usuario'];

        if ($usuario['tipo_usuario'] === 'portaria') {
            header('Location: /portaria/dashboard.php');
            exit;
        }

        header('Location: /admin/dashboard.php');
        exit;
    }

    $error = 'Login inválido.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SCP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h4 mb-3">SCP - Login</h1>
                        <?php if ($error): ?><div class="alert alert-danger"><?php echo escapeHTML($error); ?></div><?php endif; ?>
                        <form method="post">
                            <div class="mb-3"><label class="form-label">E-mail</label><input type="email" name="email" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Senha</label><input type="password" name="senha" class="form-control" required></div>
                            <button class="btn btn-primary w-100">Entrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
