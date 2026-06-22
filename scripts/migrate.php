<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("Somente CLI\n");
require __DIR__ . '/../config/database.php';

$pdo=db();
$pdo->exec('CREATE TABLE IF NOT EXISTS scp_migrations (nome VARCHAR(190) PRIMARY KEY, executada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$files=glob(__DIR__.'/../database/migrations/*.sql')?:[];
sort($files);
foreach($files as $file){
    $name=basename($file);
    $q=$pdo->prepare('SELECT COUNT(*) FROM scp_migrations WHERE nome=?');$q->execute([$name]);
    if($q->fetchColumn()){echo "Já aplicada: $name\n";continue;}
    $statements=array_filter(array_map('trim',explode(';',(string)file_get_contents($file))));
    foreach($statements as $statement){
        try{$pdo->exec($statement);}catch(PDOException $error){if(!in_array($error->getCode(),['42S01','42S21'],true))throw $error;}
    }
    $q=$pdo->prepare('INSERT INTO scp_migrations(nome) VALUES(?)');$q->execute([$name]);
    echo "Aplicada: $name\n";
}
echo "Migrações concluídas.\n";
