<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Somente CLI\n");
}

$root = dirname(__DIR__);
$errors = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        static fn(SplFileInfo $file): bool => !in_array($file->getFilename(), ['.repo', '.git', 'vendor', 'node_modules'], true)
    )
);

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile()) continue;
    $path = $file->getPathname();
    $relative = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
    if ($file->getExtension() === 'php') {
        $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path);
        exec($cmd, $output, $code);
        if ($code !== 0) {
            $errors++;
            echo "PHP inválido: {$relative}\n";
            echo implode("\n", $output) . "\n";
        }
    }
    if ($file->getExtension() === 'json' || $file->getFilename() === 'manifest.webmanifest') {
        json_decode((string)file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors++;
            echo "JSON inválido: {$relative} — " . json_last_error_msg() . "\n";
        }
    }
}

if ($errors > 0) {
    echo "Falhas encontradas: {$errors}\n";
    exit(1);
}

echo "CI_CHECK_OK\n";
