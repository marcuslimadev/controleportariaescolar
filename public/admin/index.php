<?php
require __DIR__ . '/../../includes/bootstrap.php';
require_permission('student.manage');

$schoolService = \App\Support\ServiceFactory::schoolAdmin();
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$allowedTabs = ['alunos', 'responsaveis', 'turmas', 'vinculos'];
if ($isAdmin) {
    $allowedTabs = array_merge($allowedTabs, ['professores', 'usuarios']);
}
$activeTab = in_array($_GET['tab'] ?? '', $allowedTabs, true) ? (string)$_GET['tab'] : 'alunos';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $postTab = in_array($_POST['tab'] ?? '', $allowedTabs, true) ? (string)$_POST['tab'] : $activeTab;
    try {
        $schoolService->handleAction($_POST, (string)($_SESSION['role'] ?? ''));
        flash('Operação realizada.');
    } catch (Throwable $e) {
        flash('Não foi possível salvar: ' . $e->getMessage(), 'danger');
    }
    redirect('admin/index.php?tab=' . $postTab);
}

extract($schoolService->dashboardData(), EXTR_SKIP);
$titles = [
    'alunos' => 'Alunos',
    'responsaveis' => 'Responsáveis',
    'turmas' => 'Turmas',
    'vinculos' => 'Vínculos',
    'professores' => 'Professores',
    'usuarios' => 'Usuários',
];
layout_header($titles[$activeTab] ?? 'Painel da escola');
?>
<div class="page-heading">
  <div>
    <span class="gate-eyebrow">PAINEL</span>
    <h1><?=e($titles[$activeTab] ?? 'Painel da escola')?></h1>
    <p>Navegação principal pelo menu hambúrguer.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-success" href="<?=e(url('portaria/convites.php'))?>">Convidar responsável</a>
    <a class="btn btn-outline-primary" href="<?=e(url('admin/relatorios.php'))?>">Relatórios</a>
    <a class="btn btn-primary" href="<?=e(url('portaria/index.php'))?>">Abrir portaria</a>
  </div>
</div>

<?php if ($activeTab === 'alunos'): ?>
<section class="admin-panel-section">
  <div class="section-card">
    <h2>Novo aluno</h2>
    <form method="post" class="row g-2">
      <?=admin_tab_fields('aluno')?>
      <div class="col-md-4"><input class="form-control" name="nome" placeholder="Nome completo" required></div>
      <div class="col-md-2"><input class="form-control" name="cpf" placeholder="CPF"></div>
      <div class="col-md-2"><input type="date" class="form-control" name="data_nascimento"></div>
      <div class="col-md-2">
        <select class="form-select" name="turma_id">
          <option value="">Turma</option>
          <?php foreach ($scp_turmas as $t): ?><option value="<?=$t['id']?>"><?=e($t['nome'])?></option><?php endforeach ?>
        </select>
      </div>
      <div class="col-md-2"><input class="form-control" name="foto" placeholder="URL da foto"></div>
      <div><button class="btn btn-primary">Cadastrar</button></div>
    </form>
  </div>
  <?php if ($scp_alunos): ?>
    <div class="data-table-card"><div class="table-responsive"><table class="table mb-0">
      <thead><tr><th>Aluno</th><th>Turma</th><th>Status</th><th></th></tr></thead>
      <tbody><?php foreach ($scp_alunos as $a): ?><tr>
        <td><?=e($a['nome'])?></td>
        <td><?=e($a['turma'] ?? '-')?></td>
        <td><span class="status-pill <?=$a['ativo'] ? 'active' : 'inactive'?>"><?=$a['ativo'] ? 'Ativo' : 'Inativo'?></span></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?=e(url('admin/seguranca.php?id=' . $a['id']))?>">QR de segurança</a> <?=toggle_form('scp_alunos', $a)?></td>
      </tr><?php endforeach ?></tbody>
    </table></div></div>
  <?php else: ?><div class="empty-state">Nenhum aluno cadastrado ainda.</div><?php endif ?>
</section>
<?php endif ?>

<?php if ($activeTab === 'responsaveis'): ?>
<section class="admin-panel-section">
  <?=simple_form('responsavel', ['nome' => 'Nome', 'cpf' => 'CPF', 'email' => 'E-mail', 'telefone' => 'Telefone', 'senha' => 'Senha inicial'], 'Cadastrar responsável')?>
  <?php if ($resp): ?>
    <div class="data-table-card"><div class="table-responsive"><table class="table mb-0">
      <thead><tr><th>Responsável</th><th>CPF</th><th>Status</th><th></th></tr></thead>
      <tbody><?php foreach ($resp as $r): ?><tr>
        <td><?=e($r['nome'])?></td>
        <td><?=e($r['cpf'])?></td>
        <td><span class="status-pill <?=$r['ativo'] ? 'active' : 'inactive'?>"><?=$r['ativo'] ? 'Ativo' : 'Inativo'?></span></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?=e(url('admin/cracha.php?responsavel_id=' . $r['id'] . '&emit=1'))?>">Crachá</a> <?=toggle_form('scp_responsaveis', $r)?></td>
      </tr><?php endforeach ?></tbody>
    </table></div></div>
  <?php else: ?><div class="empty-state">Nenhum responsável cadastrado ainda.</div><?php endif ?>
</section>
<?php endif ?>

<?php if ($activeTab === 'turmas'): ?>
<section class="admin-panel-section">
  <div class="section-card">
    <h2>Nova turma</h2>
    <form method="post" class="row g-2">
      <?=admin_tab_fields('turma')?>
      <div class="col"><input class="form-control" name="nome" placeholder="Nome da turma" required></div>
      <div class="col">
        <select class="form-select" name="turno">
          <option value="manha">Manhã</option><option value="tarde">Tarde</option><option value="integral">Integral</option><option value="noite">Noite</option>
        </select>
      </div>
      <div class="col-auto"><button class="btn btn-primary">Cadastrar</button></div>
    </form>
  </div>
  <?php if ($scp_turmas): ?>
    <div class="data-table-card"><div class="table-responsive"><table class="table mb-0">
      <thead><tr><th>Turma</th><th>Turno</th><th>Status</th><th></th></tr></thead>
      <tbody><?php foreach ($scp_turmas as $t): ?><tr>
        <td><?=e($t['nome'])?></td>
        <td><?=e(ucfirst((string)$t['turno']))?></td>
        <td><span class="status-pill <?=$t['ativo'] ? 'active' : 'inactive'?>"><?=$t['ativo'] ? 'Ativo' : 'Inativo'?></span></td>
        <td class="text-end"><?=toggle_form('scp_turmas', $t)?></td>
      </tr><?php endforeach ?></tbody>
    </table></div></div>
  <?php endif ?>
</section>
<?php endif ?>

<?php if ($activeTab === 'vinculos'): ?>
<section class="admin-panel-section">
  <div class="section-card">
    <h2>Vincular responsável a aluno</h2>
    <form method="post" class="row g-3">
      <?=admin_tab_fields('vinculo')?>
      <div class="col-md">
        <select class="form-select" name="aluno_id" required>
          <option value="">Aluno</option>
          <?php foreach ($scp_alunos as $a): ?><option value="<?=$a['id']?>"><?=e($a['nome'])?></option><?php endforeach ?>
        </select>
      </div>
      <div class="col-md">
        <select class="form-select" name="responsavel_id" required>
          <option value="">Responsável</option>
          <?php foreach ($resp as $r): ?><option value="<?=$r['id']?>"><?=e($r['nome'])?></option><?php endforeach ?>
        </select>
      </div>
      <div class="col-md"><input class="form-control" name="parentesco" placeholder="Parentesco" required></div>
      <div class="col-12"><label class="me-3"><input type="checkbox" name="consulta" checked> Consulta</label><label><input type="checkbox" name="retirada"> Retirada</label></div>
      <div><button class="btn btn-primary">Vincular</button></div>
    </form>
  </div>
</section>
<?php endif ?>

<?php if ($isAdmin && $activeTab === 'professores'): ?>
<section class="admin-panel-section">
  <div class="section-card">
    <h2>Novo professor</h2>
    <form method="post" class="row g-2">
      <?=admin_tab_fields('professor')?>
      <div class="col-md"><input class="form-control" name="nome" placeholder="Nome" required></div>
      <div class="col-md"><input class="form-control" name="email" placeholder="E-mail"></div>
      <div class="col-md"><input class="form-control" name="telefone" placeholder="Telefone"></div>
      <div class="col-md">
        <select class="form-select" name="usuario_id">
          <option value="">Sem usuário</option>
          <?php foreach ($users as $u): ?><option value="<?=$u['id']?>"><?=e($u['nome'])?> - <?=e($u['perfil'])?></option><?php endforeach ?>
        </select>
      </div>
      <div class="col-auto"><button class="btn btn-primary">Cadastrar</button></div>
    </form>
  </div>
  <div class="section-card">
    <h2>Vincular professor à turma</h2>
    <form method="post" class="row g-2">
      <?=admin_tab_fields('professor_turma')?>
      <div class="col-md"><select class="form-select" name="professor_id" required><option value="">Professor</option><?php foreach ($professores as $p): ?><option value="<?=$p['id']?>"><?=e($p['nome'])?></option><?php endforeach ?></select></div>
      <div class="col-md"><select class="form-select" name="turma_id" required><option value="">Turma</option><?php foreach ($scp_turmas as $t): ?><option value="<?=$t['id']?>"><?=e($t['nome'])?></option><?php endforeach ?></select></div>
      <div class="col-auto"><button class="btn btn-primary">Vincular</button></div>
    </form>
  </div>
  <?php if ($professores): ?>
    <div class="data-table-card"><div class="table-responsive"><table class="table mb-0">
      <thead><tr><th>Professor</th><th>E-mail</th><th>Turmas</th><th>Status</th><th></th></tr></thead>
      <tbody><?php foreach ($professores as $p): ?><tr>
        <td><?=e($p['nome'])?></td>
        <td><?=e($p['email'] ?: $p['usuario_email'] ?: '-')?></td>
        <td><?=e($profTurmas[$p['id']] ?? '-')?></td>
        <td><span class="status-pill <?=$p['ativo'] ? 'active' : 'inactive'?>"><?=$p['ativo'] ? 'Ativo' : 'Inativo'?></span></td>
        <td class="text-end"><?=toggle_form('scp_professores', $p)?></td>
      </tr><?php endforeach ?></tbody>
    </table></div></div>
  <?php endif ?>
</section>
<?php endif ?>

<?php if ($isAdmin && $activeTab === 'usuarios'): ?>
<section class="admin-panel-section">
  <?=simple_form('usuario', ['nome' => 'Nome', 'email' => 'E-mail', 'senha' => 'Senha'], 'Cadastrar usuário', '<select class="form-select" name="perfil"><option value="portaria">Portaria</option><option value="secretaria">Secretaria</option><option value="professor">Professor</option><option value="admin">Administrador</option></select>')?>
</section>
<?php endif ?>

<?php
layout_footer();

function admin_tab_fields(string $action): string
{
    global $activeTab;
    return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">' .
        '<input type="hidden" name="tab" value="' . e($activeTab) . '">' .
        '<input type="hidden" name="action" value="' . e($action) . '">';
}

function toggle_form(string $table, array $row): string
{
    return '<form method="post" class="d-inline">' . admin_tab_fields('toggle') .
        '<input type="hidden" name="table" value="' . e($table) . '">' .
        '<input type="hidden" name="id" value="' . (int)$row['id'] . '">' .
        '<button class="btn btn-sm btn-outline-' . ($row['ativo'] ? 'danger' : 'success') . '">' . ($row['ativo'] ? 'Inativar' : 'Ativar') . '</button></form>';
}

function simple_form(string $action, array $fields, string $button, string $extra = ''): string
{
    $html = '<div class="section-card"><h2>' . e($button) . '</h2><form method="post" class="row g-2">' . admin_tab_fields($action);
    foreach ($fields as $name => $placeholder) {
        $html .= '<div class="col-md"><input class="form-control" ' . ($name === 'senha' ? 'type="password" minlength="8" ' : '') .
            'name="' . e($name) . '" placeholder="' . e($placeholder) . '" required></div>';
    }
    return $html . $extra . '<div class="col-auto"><button class="btn btn-primary">' . e($button) . '</button></div></form></div>';
}
