<?php
if (PHP_SAPI !== 'cli') exit("Somente CLI\n");
require __DIR__ . '/../config/database.php';
[$script,$name,$email,$password] = $argv + [null,null,null,null];
if (!$name || !$email || !$password || strlen($password)<8) exit("Uso: php scripts/create_admin.php Nome email senha(min 8)\n");
$q=db()->prepare("INSERT INTO scp_usuarios(nome,email,senha_hash,perfil) VALUES(?,?,?,'admin') ON DUPLICATE KEY UPDATE nome=VALUES(nome),senha_hash=VALUES(senha_hash),ativo=1");
$q->execute([$name,strtolower($email),password_hash($password,PASSWORD_DEFAULT)]); echo "Administrador criado.\n";

