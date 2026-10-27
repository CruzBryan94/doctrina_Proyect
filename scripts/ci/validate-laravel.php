<?php

// Reuse the same pre-boot isolation as PHPUnit; never cache the local environment.
require __DIR__.'/bootstrap.php';

$ciKernel = $ciApp->make(Illuminate\Contracts\Console\Kernel::class);
foreach (['package:discover', 'config:cache', 'route:cache', 'view:cache', 'permission:cache-reset'] as $command) {
    $status = $ciKernel->call($command);
    fwrite(STDOUT, $ciKernel->output());
    if ($status !== 0) {
        exit($status);
    }
}
