<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $config): void {
    $pluginBasePath = __DIR__ . '/plugins';

    $paths = [];

    foreach (glob($pluginBasePath . '/*') as $pluginDir) {
        if (!is_dir($pluginDir)) {
            continue;
        }

        foreach (['src', 'resources'] as $sub) {
            $subPath = $pluginDir . '/' . $sub;
            if (is_dir($subPath)) {
                $paths[] = $subPath;
            }
        }
    }

    $config->paths($paths);

    $config->phpVersion(PhpVersion::PHP_82);

    $config->sets([
        LevelSetList::UP_TO_PHP_82,
    ]);
};
