<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/portal.php';

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $path = __DIR__ . '/../app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($path)) require_once $path;
});

function csp_nonce(): string { static $nonce = null; return $nonce ??= bin2hex(random_bytes(16)); }
function send_security_headers(): void {
    if (headers_sent()) return;
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-" . csp_nonce() . "' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data: blob: https:; connect-src 'self'; font-src 'self' data: https://cdn.jsdelivr.net; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(self), geolocation=(self), microphone=()');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
send_security_headers();

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ini_set('session.cookie_secure', '1');
session_start();
if (isset($_GET['lang']) && in_array($_GET['lang'], ['pt','en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return rtrim(config()['base_url'] ?? '', '/') . '/' . ltrim($path, '/'); }
function asset_url(string $path): string { $file = __DIR__ . '/../public/' . ltrim($path, '/'); $v = is_file($file) ? filemtime($file) : time(); return url($path) . '?v=' . $v; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Sessão expirada. Atualize a página.'); } }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function flash(string $message, string $type='success'): void { $_SESSION['flash'] = [$message, $type]; }
function wants_json(): bool {
    $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    return in_array($script, ['lookup.php','registrar.php','pendencias.php'], true)
        || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
        || str_contains((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')
        || str_contains((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''), 'XMLHttpRequest');
}
function forbidden(string $message='Sem permissão.'): never {
    http_response_code(403);
    if (wants_json()) {
        header('Content-Type: application/json');
        echo json_encode(['message'=>$message], JSON_UNESCAPED_UNICODE);
    } else {
        exit($message);
    }
    exit;
}
function current_actor_role(): string { return !empty($_SESSION['responsavel_id']) ? 'responsavel' : (string)($_SESSION['role'] ?? ''); }
function has_permission(string $permission): bool { return \App\Support\Permission::roleHas(current_actor_role(), $permission); }
function require_permission(string $permission): void {
    if (empty($_SESSION['user_id']) && empty($_SESSION['responsavel_id'])) redirect('login.php');
    if (!has_permission($permission)) forbidden();
}
function password_hash_secure(string $plain): string { return \App\Support\PasswordService::hash($plain); }
function password_verify_secure(string $plain, string $hash): bool { return \App\Support\PasswordService::verify($plain, $hash); }
function password_needs_rehash_secure(string $hash): bool { return \App\Support\PasswordService::needsRehash($hash); }
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }
function login_rate_normalize(string $login): string { return strtolower(trim($login)); }
function login_rate_key(string $login): string { return hash('sha256', client_ip() . '|' . login_rate_normalize($login)); }
function login_rate_blocked(string $login): bool {
    try {
        $q = db()->prepare('SELECT bloqueado_ate FROM scp_login_tentativas WHERE chave=? AND bloqueado_ate > NOW() LIMIT 1');
        $q->execute([login_rate_key($login)]);
        return (bool)$q->fetchColumn();
    } catch (Throwable $ignored) {
        return false;
    }
}
function login_rate_hit(string $login): void {
    try {
        $key = login_rate_key($login);
        $hash = hash('sha256', login_rate_normalize($login));
        $sql = "INSERT INTO scp_login_tentativas (chave, ip, login_hash, tentativas, ultima_tentativa)
                VALUES (?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                  ip=VALUES(ip),
                  login_hash=VALUES(login_hash),
                  tentativas=CASE WHEN ultima_tentativa < DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 ELSE tentativas + 1 END,
                  bloqueado_ate=CASE WHEN (CASE WHEN ultima_tentativa < DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 ELSE tentativas + 1 END) >= 5 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE) ELSE bloqueado_ate END,
                  ultima_tentativa=NOW()";
        db()->prepare($sql)->execute([$key, client_ip(), $hash]);
    } catch (Throwable $ignored) {}
}
function login_rate_clear(string $login): void {
    try {
        db()->prepare('DELETE FROM scp_login_tentativas WHERE chave=?')->execute([login_rate_key($login)]);
    } catch (Throwable $ignored) {}
}
function audit(string $action, ?string $entity=null, ?int $entityId=null, array $details=[]): void {
    $s = db()->prepare('INSERT INTO scp_logs_auditoria (usuario_id, responsavel_id, acao, entidade, entidade_id, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $s->execute([$_SESSION['user_id'] ?? null, $_SESSION['responsavel_id'] ?? null, $action, $entity, $entityId, json_encode($details, JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)]);
}
function require_role(array $roles): void { if (empty($_SESSION['user_id'])) redirect('login.php'); if (!in_array($_SESSION['role'] ?? '', $roles, true)) forbidden(); }
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
    echo '<!doctype html><html lang="'.e(current_locale()==='en'?'en':'pt-BR').'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light"><meta name="theme-color" content="#1356A2"><meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"><meta name="apple-mobile-web-app-title" content="'.e(app_name()).'"><title>'.e($title).' · '.e(app_name()).'</title><link rel="manifest" href="'.e(url('manifest.webmanifest')).'"><link rel="icon" href="'.e(url('assets/porta-aberta-icon-512.png')).'" type="image/png"><link rel="apple-touch-icon" href="'.e(url('assets/porta-aberta-icon-512.png')).'"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="'.e(asset_url('assets/app.css')).'"></head><body'.$bodyClass.'>';
    if (!empty($_SESSION['user_id']) || !empty($_SESSION['responsavel_id'])) echo '<nav class="navbar navbar-dark bg-primary"><div class="container"><a class="navbar-brand brand-lockup" href="'.e(url(portal_home())).'"><img src="'.e(asset_url('assets/porta-aberta-logo.jpg')).'" alt="'.e(app_name()).'"><span>'.e(app_name()).'</span></a><div class="d-flex gap-2 align-items-center"><a class="btn btn-outline-light btn-sm" href="'.e(lang_url(current_locale()==='en'?'pt':'en')).'">'.e(current_locale()==='en'?'PT':'EN').'</a><a class="btn btn-outline-light btn-sm" href="'.e(url('logout.php')).'">'.e(t('logout')).'</a></div></div></nav>'.portal_nav_html();
    echo '<main class="container pb-5">'; if ($flash) echo '<div class="alert alert-'.e($flash[1]).'">'.e($flash[0]).'</div>';
}
function layout_footer(): void { echo '</main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script nonce="'.e(csp_nonce()).'">if("serviceWorker" in navigator){window.addEventListener("load",()=>navigator.serviceWorker.register("'.e(url('sw.js')).'"));}</script></body></html>'; }
