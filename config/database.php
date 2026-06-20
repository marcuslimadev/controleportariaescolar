<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'controle_portaria_escolar');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);
define('SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);
define('APP_BASE_URL', getenv('APP_BASE_URL') ?: 'https://scp.lojadaesquina.store');
define('SCHOOL_NAME', getenv('SCHOOL_NAME') ?: 'Escola cadastrada');
define('SCHOOL_PHONE', getenv('SCHOOL_PHONE') ?: '');
define('SCHOOL_WHATSAPP', getenv('SCHOOL_WHATSAPP') ?: '');

function getDBConnection() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        die('Erro de conexão com banco de dados: ' . htmlspecialchars($e->getMessage()));
    }
}

function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        die('Erro na execução da query: ' . htmlspecialchars($e->getMessage()));
    }
}
?>
