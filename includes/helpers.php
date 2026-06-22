<?php
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) session_start();
}
function escapeHTML($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
function sanitizeInput($input) {
    return trim(strip_tags($input ?? ''));
}
function getClientIP() {
    return $_SERVER['REMOTE_ADDR'] ?? '';
}
function getDeviceInfo() {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}
function logAuditAction($pdo, $userId, $action, $table, $recordId, $oldData = null, $newData = null) {
    return true;
}
?>
