<?php
require __DIR__ . '/../includes/bootstrap.php';
require_portal_access();

$profileService = \App\Support\ServiceFactory::profiles();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $photoUrl = save_portal_upload($_FILES['foto'] ?? [], 'perfis', 'image');
        if (!$photoUrl) {
            throw new RuntimeException(current_locale() === 'en' ? 'Choose a profile photo.' : 'Escolha uma foto de perfil.');
        }
        $profileService->updatePhoto($_SESSION['user_id'] ?? null, $_SESSION['responsavel_id'] ?? null, $photoUrl);
        $_SESSION['photo'] = $photoUrl;
        flash(current_locale() === 'en' ? 'Profile photo updated.' : 'Foto de perfil atualizada.');
        redirect('perfil.php');
    } catch (Throwable $error) {
        flash((current_locale() === 'en' ? 'Unable to save: ' : 'Não foi possível salvar: ') . $error->getMessage(), 'danger');
        redirect('perfil.php');
    }
}

try {
    $profileData = $profileService->currentProfile($_SESSION['user_id'] ?? null, $_SESSION['responsavel_id'] ?? null);
} catch (Throwable $error) {
    flash($error->getMessage(), 'warning');
    redirect(portal_home());
}

$profile = $profileData['profile'];
$photo = $profile['foto'] ?: app_logo_url();
$name = (string)($profile['nome'] ?? $_SESSION['name'] ?? app_name());
$role = (string)($_SESSION['role'] ?? $profileData['type']);
$roleLabels = [
    'admin' => current_locale() === 'en' ? 'Administrator' : 'Administrador',
    'secretaria' => current_locale() === 'en' ? 'Office staff' : 'Secretaria',
    'professor' => current_locale() === 'en' ? 'Teacher' : 'Professor',
    'portaria' => current_locale() === 'en' ? 'Gatehouse' : 'Portaria',
    'responsavel' => current_locale() === 'en' ? 'Guardian' : 'Responsável',
    'usuario' => current_locale() === 'en' ? 'Staff user' : 'Usuário interno',
];

layout_header(current_locale() === 'en' ? 'My profile' : 'Meu perfil');
?>
<div class="page-heading">
  <div>
    <span class="gate-eyebrow"><?=e(current_locale() === 'en' ? 'IDENTITY' : 'IDENTIDADE')?></span>
    <h1><?=e(current_locale() === 'en' ? 'My profile' : 'Meu perfil')?></h1>
    <p><?=e(current_locale() === 'en' ? 'Choose the photo that will identify your posts and actions.' : 'Escolha a foto que identifica suas postagens e ações.')?></p>
  </div>
</div>

<section class="profile-shell">
  <article class="profile-card">
    <div class="profile-photo-frame">
      <img class="profile-photo" src="<?=e(media_url($photo, $profile['updated_at'] ?? $profile['id'] ?? ''))?>" alt="<?=e($name)?>">
    </div>
    <div class="profile-summary">
      <span><?=e($roleLabels[$role] ?? ucfirst($role))?></span>
      <h2><?=e($name)?></h2>
      <?php if (!empty($profile['email'])): ?><p><?=e($profile['email'])?></p><?php endif ?>
      <?php if (!empty($profile['telefone'])): ?><p><?=e($profile['telefone'])?></p><?php endif ?>
    </div>
  </article>

  <form method="post" enctype="multipart/form-data" class="section-card profile-upload-card">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <h2><?=e(current_locale() === 'en' ? 'Profile photo' : 'Foto de perfil')?></h2>
    <p class="text-muted"><?=e(current_locale() === 'en' ? 'Use a clear, professional image. JPG, PNG or WebP up to 8 MB.' : 'Use uma imagem nítida e profissional. JPG, PNG ou WebP até 8 MB.')?></p>
    <label class="form-label fw-bold" for="foto"><?=e(current_locale() === 'en' ? 'Choose photo' : 'Escolher foto')?></label>
    <input id="foto" class="form-control form-control-lg" type="file" name="foto" accept="image/jpeg,image/png,image/webp" required>
    <button class="btn btn-primary btn-lg mt-4" type="submit"><?=e(current_locale() === 'en' ? 'Save photo' : 'Salvar foto')?></button>
  </form>
</section>
<?php layout_footer();
