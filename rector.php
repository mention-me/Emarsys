<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(
        php82: true
    );
//    ->withPreparedSets(
//        deadCode: true,
//        codeQuality: true,
//        codingStyle: true,
//        privatization: true,
//        naming: true,
//        instanceof: true,
//        earlyReturn: true,
//    );
