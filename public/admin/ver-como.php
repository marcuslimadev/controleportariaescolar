<?php
require __DIR__ . '/../../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'stop') {
    verify_csrf();
    if (!empty($_SESSION['demo_admin']) && is_array($_SESSION['demo_admin'])) {
        $csrf = csrf();
        $lang = $_SESSION['lang'] ?? null;
        $admin = $_SESSION['demo_admin'];
        $_SESSION = [
            'user_id' => (int)$admin['user_id'],
            'role' => 'admin',
            'name' => (string)$admin['name'],
            'photo' => $admin['photo'] ?? null,
            'csrf' => $csrf,
        ];
        if ($lang) $_SESSION['lang'] = $lang;
        flash('Você voltou para o perfil de administrador.');
    }
    redirect('admin/ver-como.php');
}

require_role(['admin']);

$schoolService = \App\Support\ServiceFactory::schoolAdmin();
$data = $schoolService->dashboardData();
$users = array_values(array_filter($data['users'] ?? [], static fn(array $user): bool => !empty($user['ativo'])));
$guardians = array_values(array_filter($data['resp'] ?? [], static fn(array $guardian): bool => !empty($guardian['ativo'])));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $type = (string)($_POST['type'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $target = null;

    if ($type === 'user') {
        foreach ($users as $user) {
            if ((int)$user['id'] === $id) {
                $target = $user;
                break;
            }
        }
        if (!$target) {
            flash('Usuário não encontrado para demonstração.', 'warning');
            redirect('admin/ver-como.php');
        }
        $_SESSION['demo_admin'] = [
            'user_id' => (int)$_SESSION['user_id'],
            'name' => (string)$_SESSION['name'],
            'photo' => $_SESSION['photo'] ?? null,
        ];
        $csrf = csrf();
        $lang = $_SESSION['lang'] ?? null;
        $_SESSION = [
            'user_id' => (int)$target['id'],
            'role' => (string)$target['perfil'],
            'name' => (string)$target['nome'],
            'photo' => $target['foto'] ?? null,
            'demo_admin' => $_SESSION['demo_admin'],
            'csrf' => $csrf,
        ];
        if ($lang) $_SESSION['lang'] = $lang;
        flash('Modo demonstração ativado: ' . $target['nome']);
        redirect(portal_home());
    }

    if ($type === 'guardian') {
        foreach ($guardians as $guardian) {
            if ((int)$guardian['id'] === $id) {
                $target = $guardian;
                break;
            }
        }
        if (!$target) {
            flash('Responsável não encontrado para demonstração.', 'warning');
            redirect('admin/ver-como.php');
        }
        $_SESSION['demo_admin'] = [
            'user_id' => (int)$_SESSION['user_id'],
            'name' => (string)$_SESSION['name'],
            'photo' => $_SESSION['photo'] ?? null,
        ];
        $csrf = csrf();
        $lang = $_SESSION['lang'] ?? null;
        $_SESSION = [
            'responsavel_id' => (int)$target['id'],
            'name' => (string)$target['nome'],
            'photo' => $target['foto'] ?? null,
            'demo_admin' => $_SESSION['demo_admin'],
            'csrf' => $csrf,
        ];
        if ($lang) $_SESSION['lang'] = $lang;
        flash('Modo demonstração ativado: ' . $target['nome']);
        redirect('feed.php');
    }

    flash('Selecione um perfil para demonstração.', 'warning');
    redirect('admin/ver-como.php');
}

$usersByRole = [];
foreach ($users as $user) {
    $usersByRole[(string)$user['perfil']][] = $user;
}

layout_header('Ver como');
?>
<div class="page-heading">
  <div>
    <span class="gate-eyebrow">DEMONSTRAÇÃO</span>
    <h1>Ver como outro perfil</h1>
    <p>Troque temporariamente a visão do sistema para demonstrar a experiência de cada usuário.</p>
  </div>
</div>

<section class="demo-role-grid">
  <?php foreach (['admin' => 'Diretor / Admin', 'secretaria' => 'Secretaria', 'professor' => 'Professor', 'portaria' => 'Porteiro'] as $role => $label): ?>
    <article class="section-card demo-role-card">
      <h2><?=e($label)?></h2>
      <p class="text-muted">Escolha um usuário ativo deste perfil.</p>
      <?php if (!empty($usersByRole[$role])): ?>
        <?php foreach ($usersByRole[$role] as $user): ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=e(csrf())?>">
            <input type="hidden" name="type" value="user">
            <input type="hidden" name="id" value="<?=(int)$user['id']?>">
            <button class="btn btn-outline-primary w-100" type="submit"><?=e($user['nome'])?></button>
          </form>
        <?php endforeach ?>
      <?php else: ?>
        <span class="text-muted">Nenhum usuário ativo.</span>
      <?php endif ?>
    </article>
  <?php endforeach ?>

  <article class="section-card demo-role-card">
    <h2>Pai / Responsável</h2>
    <p class="text-muted">Escolha um responsável ativo para demonstrar o portal da família.</p>
    <?php if ($guardians): ?>
      <?php foreach (array_slice($guardians, 0, 20) as $guardian): ?>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=e(csrf())?>">
          <input type="hidden" name="type" value="guardian">
          <input type="hidden" name="id" value="<?=(int)$guardian['id']?>">
          <button class="btn btn-outline-primary w-100" type="submit"><?=e($guardian['nome'])?></button>
        </form>
      <?php endforeach ?>
    <?php else: ?>
      <span class="text-muted">Nenhum responsável ativo.</span>
    <?php endif ?>
  </article>
</section>
<?php layout_footer();
