<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class RectorRefactorService
{
    /**
     * @param string $inputPath
     * @return array
     */
    public function run(string $inputPath): array
    {
        $inputPath = rtrim($inputPath, '/');

        if (!is_dir($inputPath)) {
            throw new \InvalidArgumentException("Invalid path: $inputPath");
        }

        $pluginFolders = array_filter(glob($inputPath . '/*'), 'is_dir');

        if (empty($pluginFolders)) {
            throw new \RuntimeException("No plugins found in: {$inputPath}");
        }

        $rectorBinary = base_path('vendor/bin/rector');
        $configPath = base_path('rector.php');

        if (!file_exists($rectorBinary)) {
            throw new \RuntimeException("Rector is not installed. Please run `composer install`.");
        }

        if (!file_exists($configPath)) {
            throw new \RuntimeException("Missing rector.php config at project root.");
        }

        $results = [];

        foreach ($pluginFolders as $pluginPath) {
            $pluginName = basename($pluginPath);

            if (!file_exists($pluginPath . '/plugin.json')) {
                $results[$pluginName] = [
                    'success' => false,
                    'output' => '',
                    'errorOutput' => "Missing plugin.json in plugin: $pluginName",
                ];
                continue;
            }

            try {
                $results[$pluginName] = $this->runRector($rectorBinary, $configPath, $pluginPath);
            } catch (\Throwable $e) {
                $results[$pluginName] = [
                    'success' => false,
                    'output' => '',
                    'errorOutput' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * @param string $rectorBinary
     * @param string $configPath
     * @param string $pluginPath
     * @return array
     */
    private function runRector(string $rectorBinary, string $configPath, string $pluginPath): array
    {
        $command = "$rectorBinary process $pluginPath --config=$configPath";

        $process = Process::fromShellCommandline($command, $pluginPath);
        $process->setTimeout(60);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'errorOutput' => $process->getErrorOutput(),
        ];
    }
}
