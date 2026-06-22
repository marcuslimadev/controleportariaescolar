<?php
function getPublicBadgeUrl($token) {
    $baseUrl = defined('APP_BASE_URL') ? rtrim(APP_BASE_URL, '/') : 'https://scp.lojadaesquina.store';
    return $baseUrl . '/c/' . rawurlencode($token);
}

function extractQRCodeToken($rawValue) {
    $value = trim((string)$rawValue);
    if ($value === '') return '';
    $decoded = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    $parts = @parse_url($decoded);
    if (is_array($parts) && !empty($parts['path']) && preg_match('#/c/([^/?#]+)#', $parts['path'], $m)) {
        return preg_replace('/[^a-zA-Z0-9]/', '', rawurldecode($m[1]));
    }
    return preg_replace('/[^a-zA-Z0-9]/', '', $decoded);
}

function getStudentByToken($pdo, $token) {
    $token = extractQRCodeToken($token);
    $sql = "SELECT a.id, a.nome, a.status, t.nome AS turma_nome FROM alunos a LEFT JOIN turmas t ON t.id = a.turma_id WHERE a.token_qrcode = ? AND a.status = 'ativo'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token]);
    return $stmt->fetch();
}
?>
