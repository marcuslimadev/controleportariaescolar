<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ini_set('session.cookie_secure', '1');
session_start();

function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return rtrim(config()['base_url'] ?? '', '/') . '/' . ltrim($path, '/'); }
function asset_url(string $path): string { $file = __DIR__ . '/../public/' . ltrim($path, '/'); $v = is_file($file) ? filemtime($file) : time(); return url($path) . '?v=' . $v; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Sessão expirada. Atualize a página.'); } }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function flash(string $message, string $type='success'): void { $_SESSION['flash'] = [$message, $type]; }
function audit(string $action, ?string $entity=null, ?int $entityId=null, array $details=[]): void {
    $s = db()->prepare('INSERT INTO scp_logs_auditoria (usuario_id, responsavel_id, acao, entidade, entidade_id, detalhes, ip) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $s->execute([$_SESSION['user_id'] ?? null, $_SESSION['responsavel_id'] ?? null, $action, $entity, $entityId, json_encode($details, JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR'] ?? null]);
}
function require_role(array $roles): void { if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $roles, true)) redirect('login.php'); }
function require_parent(): void { if (empty($_SESSION['responsavel_id'])) redirect('login.php'); }
function normalize_phone(string $phone): string { return preg_replace('/\D+/', '', $phone) ?: ''; }
function extract_qr_token(string $value): string {
    $value=trim(urldecode($value));
    if ($value==='') return '';
    if (filter_var($value,FILTER_VALIDATE_URL)) {
        $query=[]; parse_str((string)parse_url($value,PHP_URL_QUERY),$query);
        if (!empty($query['token'])) $value=(string)$query['token'];
        else { $path=trim((string)parse_url($value,PHP_URL_PATH),'/'); $value=(string)basename($path); }
    } elseif (str_contains($value,'/')) {
        $value=(string)basename(trim($value,'/'));
    }
    return preg_match('/^[A-Za-z0-9]{16,255}$/',$value)?$value:'';
}
function save_uploaded_image(array $file, string $folder): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Envie as duas fotos para continuar.');
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) throw new RuntimeException('Cada foto deve ter no máximo 8 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Use fotos JPG, PNG ou WebP.');
    $directory=__DIR__.'/../public/uploads/'.$folder;
    if (!is_dir($directory) && !mkdir($directory,0755,true)) throw new RuntimeException('Não foi possível preparar a pasta de fotos.');
    $filename=bin2hex(random_bytes(16)).'.'.$extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'],$directory.'/'.$filename)) throw new RuntimeException('Não foi possível salvar uma das fotos.');
    return url('uploads/'.$folder.'/'.$filename);
}
function layout_header(string $title): void {
    $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
    $bodyClass=$title==='Entrar'?' class="login-page"':'';
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#075985"><meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"><meta name="apple-mobile-web-app-title" content="SCP Portaria"><title>'.e($title).'</title><link rel="manifest" href="'.e(url('manifest.webmanifest')).'"><link rel="icon" href="'.e(url('assets/icon.svg')).'" type="image/svg+xml"><link rel="apple-touch-icon" href="'.e(url('assets/icon.svg')).'"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="'.e(asset_url('assets/app.css')).'"></head><body'.$bodyClass.'>';
    if (!empty($_SESSION['user_id']) || !empty($_SESSION['responsavel_id'])) echo '<nav class="navbar navbar-dark bg-primary mb-4"><div class="container"><a class="navbar-brand fw-bold" href="'.e(url('index.php')).'">SCP Escolar</a><a class="btn btn-outline-light btn-sm" href="'.e(url('logout.php')).'">Sair</a></div></nav>';
    echo '<main class="container pb-5">'; if ($flash) echo '<div class="alert alert-'.e($flash[1]).'">'.e($flash[0]).'</div>';
}
function layout_footer(): void { echo '</main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script>if("serviceWorker" in navigator){window.addEventListener("load",()=>navigator.serviceWorker.register("'.e(url('sw.js')).'"));}</script></body></html>'; }
