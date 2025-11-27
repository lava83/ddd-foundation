<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Exception\Configuration\InvalidConfigurationException;

try {
    return RectorConfig::configure()
        ->withPaths([
            __DIR__ . '/src',
        ])
        ->withPreparedSets(
            deadCode: true,
            codeQuality: true,
            codingStyle: true,
            typeDeclarations: true,
            privatization: true,
            earlyReturn: true,
        )->withPhpSets(php82: true);
} catch (InvalidConfigurationException $e) {
    echo 'Rector configuration error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}