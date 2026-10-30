<?php

// Infrastructure-only bootstrap: isolate tests before any RefreshDatabase hook runs.
require dirname(__DIR__, 2).'/vendor/autoload.php';

if (! extension_loaded('pdo_sqlite')) {
    throw new RuntimeException('CI requires pdo_sqlite; no fallback database is allowed.');
}

// Relative cache paths also work with Laravel 10 on Windows drive-letter paths.
// This directory is already ignored by storage/framework/testing/.gitignore.
$ciRelativeRoot = 'storage/framework/testing/ci-'.bin2hex(random_bytes(12));
$ciRoot = dirname(__DIR__, 2).'/'.$ciRelativeRoot;
foreach (['storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions',
    'storage/framework/views', 'storage/logs', 'storage/fonts', 'bootstrap/cache'] as $directory) {
    if (! mkdir($ciRoot.'/'.$directory, 0700, true) && ! is_dir($ciRoot.'/'.$directory)) {
        throw new RuntimeException('Unable to initialize isolated CI storage.');
    }
}

$ciEnvironment = [
    'APP_ENV' => 'testing',
    'APP_DEBUG' => 'false',
    // Public, disposable test key. Never use in Railway.
    'APP_KEY' => 'base64:'.base64_encode(str_repeat('t', 32)),
    'APP_URL' => 'http://localhost',
    'APP_BASE_PATH' => dirname(__DIR__, 2),
    'APP_CONFIG_CACHE' => $ciRelativeRoot.'/bootstrap/cache/config.php',
    'APP_SERVICES_CACHE' => $ciRelativeRoot.'/bootstrap/cache/services.php',
    'APP_PACKAGES_CACHE' => $ciRelativeRoot.'/bootstrap/cache/packages.php',
    'APP_ROUTES_CACHE' => $ciRelativeRoot.'/bootstrap/cache/routes.php',
    'APP_EVENTS_CACHE' => $ciRelativeRoot.'/bootstrap/cache/events.php',
    'LARAVEL_STORAGE_PATH' => $ciRoot.'/storage',
    'VIEW_COMPILED_PATH' => $ciRoot.'/storage/framework/views',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DATABASE_URL' => '',
    'DB_HOST' => '127.0.0.1',
    'DB_USERNAME' => 'ci_unused',
    'DB_PASSWORD' => '',
    'DB_SOCKET' => '',
    'CACHE_DRIVER' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'array',
    'LOG_CHANNEL' => 'stderr',
    'LOG_LEVEL' => 'error',
    'BCRYPT_ROUNDS' => '4',
    'BROADCAST_DRIVER' => 'log',
];
foreach ($ciEnvironment as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}

// Check effective Laravel configuration, not just the exported environment.
$ciApp = require dirname(__DIR__, 2).'/bootstrap/app.php';
$ciApp->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$ciConfig = $ciApp->make('config');
if ($ciApp->configurationIsCached()
    || $ciConfig->get('database.default') !== 'sqlite'
    || $ciConfig->get('database.connections.sqlite.database') !== ':memory:'
    || ! empty($ciConfig->get('database.connections.sqlite.url'))
    || $ciApp->storagePath() !== $ciRoot.'/storage') {
    throw new RuntimeException('Unsafe effective test configuration; refusing to run tests.');
}

fwrite(STDOUT, "CI isolation verified: SQLite :memory:, fresh temporary storage, no production credentials.\n");
