<?php
require __DIR__ . '/../includes/bootstrap.php';
require_portal_access();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('notificacoes.php');
verify_csrf();
\App\Support\ServiceFactory::notifications()->markAllRead($_SESSION['user_id'] ?? null, $_SESSION['responsavel_id'] ?? null);
flash(current_locale()==='en' ? 'Notifications marked as read.' : 'Notificações marcadas como lidas.');
redirect('notificacoes.php');
