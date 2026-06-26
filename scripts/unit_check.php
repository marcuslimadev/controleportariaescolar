<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Somente CLI\n");
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $path = __DIR__ . '/../app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($path)) require_once $path;
});

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_EXCEPTION, 1);

$tests = glob(__DIR__ . '/../tests/Unit/*Test.php') ?: [];
foreach ($tests as $test) {
    $runner = require $test;
    $runner();
}

echo 'UNIT_CHECK_OK ' . count($tests) . " tests\n";
