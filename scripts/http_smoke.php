<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Somente CLI\n");
}

$base = rtrim($argv[1] ?? getenv('SCP_BASE_URL') ?: 'https://scp.lojadaesquina.store', '/') . '/';

function http_get(string $url): array
{
    $headers = [];
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 20,
            'ignore_errors' => true,
            'header' => "User-Agent: PortaAbertaSmoke/1.0\r\n",
        ],
    ]);
    $body = file_get_contents($url, false, $context);
    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $line, $match)) {
            $headers[':status'] = (int)$match[1];
            continue;
        }
        if (str_contains($line, ':')) {
            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }
    }

    return ['status' => $headers[':status'] ?? 0, 'headers' => $headers, 'body' => (string)$body];
}

function assert_ok(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$login = http_get($base . 'login.php');
assert_ok($login['status'] === 200, 'Login não respondeu 200.');
assert_ok(str_contains($login['body'], 'Acesse sua conta'), 'Login sem conteúdo esperado.');
assert_ok(!empty($login['headers']['content-security-policy']), 'Header CSP ausente.');
assert_ok(($login['headers']['x-content-type-options'] ?? '') === 'nosniff', 'Header nosniff ausente.');

$manifest = http_get($base . 'manifest.webmanifest');
assert_ok($manifest['status'] === 200, 'Manifest não respondeu 200.');
$manifestJson = json_decode($manifest['body'], true);
assert_ok(is_array($manifestJson) && ($manifestJson['name'] ?? '') === 'Porta Aberta Escolar', 'Manifest inválido.');

$events = http_get($base . 'eventos.php');
assert_ok($events['status'] === 200, 'Eventos não respondeu 200.');
assert_ok(str_contains($events['body'], 'Eventos e programação'), 'Eventos sem conteúdo esperado.');

$sw = http_get($base . 'sw.js');
assert_ok($sw['status'] === 200, 'Service Worker não respondeu 200.');
assert_ok(str_contains($sw['body'], 'porta-aberta'), 'Service Worker sem cache esperado.');

echo "HTTP_SMOKE_OK {$base}\n";
