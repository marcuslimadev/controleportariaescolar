<?php
require __DIR__ . '/../includes/bootstrap.php';
require_portal_access();

$profileService = \App\Support\ServiceFactory::profiles();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $updatedProfile = $profileService->updateProfile($_SESSION['user_id'] ?? null, $_SESSION['responsavel_id'] ?? null, $_POST);
        $_SESSION['name'] = $updatedProfile['name'];
        $photoUrl = save_portal_upload($_FILES['foto'] ?? [], 'perfis', 'image');
        if ($photoUrl) {
            $profileService->updatePhoto($_SESSION['user_id'] ?? null, $_SESSION['responsavel_id'] ?? null, $photoUrl);
            $_SESSION['photo'] = $photoUrl;
        }
        flash(current_locale() === 'en' ? 'Profile updated.' : 'Perfil atualizado.');
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
$bio = (string)($profile['bio'] ?? '');
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
      <?php if ($bio !== ''): ?><p class="profile-bio"><?=e($bio)?></p><?php endif ?>
      <?php if (!empty($profile['email'])): ?><p><?=e($profile['email'])?></p><?php endif ?>
      <?php if (!empty($profile['telefone'])): ?><p><?=e($profile['telefone'])?></p><?php endif ?>
    </div>
  </article>

  <form method="post" enctype="multipart/form-data" class="section-card profile-upload-card">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <h2><?=e(current_locale() === 'en' ? 'Profile data' : 'Dados do perfil')?></h2>
    <p class="text-muted"><?=e(current_locale() === 'en' ? 'Edit your name, short bio and photo.' : 'Edite seu nome, uma bio curta e a foto.')?></p>
    <label class="form-label fw-bold" for="nome"><?=e(current_locale() === 'en' ? 'Name' : 'Nome')?></label>
    <input id="nome" class="form-control form-control-lg" name="nome" maxlength="150" value="<?=e($name)?>" required>
    <label class="form-label fw-bold mt-3" for="bio"><?=e(current_locale() === 'en' ? 'Bio' : 'Bio')?></label>
    <input id="bio" class="form-control form-control-lg" name="bio" maxlength="80" value="<?=e($bio)?>" placeholder="<?=e(current_locale() === 'en' ? 'Up to 80 characters' : 'Até 80 caracteres')?>">
    <small id="bio-count" class="profile-bio-count">0/80</small>
    <div class="profile-preview-box">
      <img id="profile-preview" src="<?=e(media_url($photo, $profile['updated_at'] ?? $profile['id'] ?? ''))?>" alt="<?=e(current_locale() === 'en' ? 'Selected photo preview' : 'Prévia da foto selecionada')?>">
      <span><?=e(current_locale() === 'en' ? 'Preview before saving' : 'Prévia antes de salvar')?></span>
    </div>
    <label class="form-label fw-bold" for="foto"><?=e(current_locale() === 'en' ? 'Choose photo' : 'Escolher foto')?></label>
    <input id="foto" class="form-control form-control-lg" type="file" name="foto" accept="image/jpeg,image/png,image/webp">
    <button class="btn btn-primary btn-lg mt-4" type="submit"><?=e(current_locale() === 'en' ? 'Save profile' : 'Salvar perfil')?></button>
  </form>
</section>
<script nonce="<?=e(csp_nonce())?>">
const profileInput=document.querySelector('#foto');
const profilePreview=document.querySelector('#profile-preview');
const bioInput=document.querySelector('#bio');
const bioCount=document.querySelector('#bio-count');
let profilePreviewUrl=null;
function syncBioCount(){if(bioInput&&bioCount)bioCount.textContent=`${bioInput.value.length}/80`}
if(bioInput){bioInput.addEventListener('input',syncBioCount);syncBioCount()}
if(profileInput&&profilePreview){
  profileInput.addEventListener('change',()=>{
    const file=profileInput.files&&profileInput.files[0];
    if(profilePreviewUrl){URL.revokeObjectURL(profilePreviewUrl);profilePreviewUrl=null}
    if(!file)return;
    if(!/^image\/(jpeg|png|webp)$/.test(file.type)){
      profileInput.value='';
      alert(<?=json_encode(current_locale()==='en' ? 'Use JPG, PNG or WebP.' : 'Use JPG, PNG ou WebP.')?>);
      return;
    }
    profilePreviewUrl=URL.createObjectURL(file);
    profilePreview.src=profilePreviewUrl;
  });
}
</script>
<?php layout_footer();
