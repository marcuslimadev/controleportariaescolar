<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("Somente CLI\n");
$opts = getopt('', ['source-env:', 'admin-email:', 'admin-password:', 'admin-name::', 'base-url::']);
if (empty($opts['source-env']) || empty($opts['admin-email']) || empty($opts['admin-password'])) exit("Parâmetros obrigatórios ausentes.\n");
$env = parse_ini_file($opts['source-env'], false, INI_SCANNER_RAW);
foreach (['DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD'] as $key) if (!array_key_exists($key, $env)) exit("Variável $key ausente.\n");
$config = ['app_name'=>'SCP Escolar','base_url'=>$opts['base-url'] ?? '','db'=>['host'=>$env['DB_HOST'],'port'=>$env['DB_PORT'],'name'=>$env['DB_DATABASE'],'user'=>$env['DB_USERNAME'],'pass'=>$env['DB_PASSWORD']]];
$content = "<?php\nreturn " . var_export($config, true) . ";\n";
file_put_contents(__DIR__ . '/../config/config.php', $content, LOCK_EX);
chmod(__DIR__ . '/../config/config.php', 0600);
require __DIR__ . '/../config/database.php';
$sql = file_get_contents(__DIR__ . '/../database/schema.sql');
foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
    try { db()->exec($statement); } catch (PDOException $e) { if ($e->getCode() !== '42S01') throw $e; }
}
$q=db()->prepare("INSERT INTO scp_usuarios(nome,email,senha_hash,perfil) VALUES(?,?,?,'admin') ON DUPLICATE KEY UPDATE nome=VALUES(nome),senha_hash=VALUES(senha_hash),ativo=1");
$q->execute([$opts['admin-name'] ?? 'Administrador', strtolower($opts['admin-email']), password_hash($opts['admin-password'], PASSWORD_DEFAULT)]);
$pdo = db();
$pdo->exec('CREATE TABLE IF NOT EXISTS scp_migrations (nome VARCHAR(190) PRIMARY KEY, executada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$files = glob(__DIR__ . '/../database/migrations/*.sql') ?: [];
sort($files);
foreach ($files as $file) {
    $name = basename($file);
    $check = $pdo->prepare('SELECT COUNT(*) FROM scp_migrations WHERE nome=?');
    $check->execute([$name]);
    if ($check->fetchColumn()) continue;
    foreach (array_filter(array_map('trim', explode(';', (string)file_get_contents($file)))) as $statement) {
        try { $pdo->exec($statement); } catch (PDOException $e) { if (!in_array($e->getCode(), ['42S01','42S21'], true)) throw $e; }
    }
    $done = $pdo->prepare('INSERT INTO scp_migrations(nome) VALUES(?)');
    $done->execute([$name]);
}
echo "Instalação concluída.\n";
