<?php

namespace App\Support;

class PathResolver
{
    /**
     *
     * @param string $context
     * @return string
     */
    public static function resolve(string $context = 'plugin directory'): string
    {
        $pluginPath = '/plugins';

        $realPath = realpath($pluginPath);

        if (!$realPath || !is_dir($realPath)) {
            throw new \RuntimeException("❌ Could not find $context at expected path: $pluginPath");
        }

        return $realPath;
    }
}
