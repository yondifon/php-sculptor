<?php

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/vendor/*',
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        // codeQuality: true,
        earlyReturn: true,
    );
