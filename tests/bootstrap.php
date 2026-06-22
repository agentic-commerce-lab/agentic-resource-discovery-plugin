<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Swag\\AgenticResourceDiscovery\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, \strlen($prefix));
    $path = __DIR__.'/../src/'.str_replace('\\', '/', $relative).'.php';

    if (is_file($path)) {
        require_once $path;
    }
});

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assert_same(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            "Expected %s, got %s.",
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}
