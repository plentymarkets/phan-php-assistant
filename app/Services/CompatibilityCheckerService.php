<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class CompatibilityCheckerService
{
    /**
     * @param string $inputPath
     * @return array
     */
    public function check(string $inputPath): array
    {
        $inputPath = rtrim($inputPath, '/');

        if (!is_dir($inputPath)) {
            throw new \InvalidArgumentException("Invalid plugins path: {$inputPath}");
        }

        $tmpBasePath = '/tmp/compat-check/' . uniqid('plugin_', true);
        $tmpPluginsPath = $tmpBasePath;
        mkdir($tmpPluginsPath, 0777, true);

        $pluginFolders = array_filter(glob($inputPath . '/*'), 'is_dir');

        if (empty($pluginFolders)) {
            throw new \RuntimeException("No plugins found in: {$inputPath}");
        }

        $results = [];

        $sdkPath = $tmpBasePath . '/plenty-plugin-interface';
        $this->clonePlentySdkTo($sdkPath);

        foreach ($pluginFolders as $pluginDir) {
            $pluginName = basename($pluginDir);
            if (!file_exists($pluginDir . '/plugin.json')) {
                throw new \RuntimeException("Missing plugin.json in plugin: {$pluginName}");
            }

            $destination = "{$tmpPluginsPath}/{$pluginName}";
            $this->copyDirectory($pluginDir, $destination);

            if (file_exists($destination . '/composer.json')) {
                $this->runComposerInstall($destination);
            }

            $pluginPhanConfigPath = $destination . '/.phan/config.php';
            if (!file_exists($pluginPhanConfigPath)) {
                throw new \RuntimeException("⚠️  Missing .phan/config.php in plugin: {$pluginName}.");
            }

            $this->updatePluginPhanConfig($pluginPhanConfigPath, $destination, $sdkPath);
            $results[$pluginName] = $this->runPhan($destination);
        }

        File::deleteDirectory($tmpBasePath);

        return $results;
    }

    /**
     * @param string $path
     * @return void
     */
    private function runComposerInstall(string $path): void
    {
        $process = Process::fromShellCommandline('composer install --no-interaction --no-scripts --no-dev', $path);
        $process->setTimeout(60);
        $process->run();
    }

    /**
     * @param string $pluginPath
     * @return array
     */
    private function runPhan(string $pluginPath): array
    {
        $phanBinary = base_path('vendor/bin/phan');
        $phanConfig = $pluginPath . '/.phan/config.php';

        $process = Process::fromShellCommandline(
            "$phanBinary -c $phanConfig --project-root-directory=$pluginPath",
            $pluginPath
        );

        $process->setTimeout(60);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'errorOutput' => $process->getErrorOutput(),
        ];
    }

    /**
     * @param string $configPath
     * @param string $pluginRootPath
     * @param string $sdkPath
     * @return void
     */
    private function updatePluginPhanConfig(string $configPath, string $pluginRootPath, string $sdkPath): void
    {
        /** @var array $config */
        $config = include $configPath;

        $realBasePath = realpath($pluginRootPath);
        if (!$realBasePath) {
            throw new \RuntimeException("Invalid plugin path: {$pluginRootPath}");
        }

        $directoryList = [];
        $fileList = [];

        $pluginSrcPath = $realBasePath . '/src';
        if (!is_dir($pluginSrcPath)) {
            throw new \RuntimeException("Plugin is missing 'src/' directory at: {$pluginRootPath}");
        }

        $pluginSrcFiles = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginSrcPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($pluginSrcFiles as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $realPath = $file->getRealPath();
            $relativePath = ltrim(str_replace($realBasePath, '', $realPath), '/');
            if ($relativePath === '') continue;

            $fileList[] = $relativePath;

            $dir = ltrim(str_replace($realBasePath, '', dirname($realPath)), '/');
            if ($dir !== '' && !in_array($dir, $directoryList)) {
                $directoryList[] = $dir;
            }
        }


        if (is_dir($sdkPath)) {
            $config['directory_list'][] = $sdkPath;
            $config['exclude_analysis_directory_list'][] = $sdkPath;
        }

        $config['directory_list'] = array_values(array_unique(array_filter(
            array_merge($config['directory_list'] ?? [], $directoryList),
            fn($dir) => !empty($dir)
        )));

        $config['file_list'] = array_values(array_unique(array_filter(
            array_merge($config['file_list'] ?? [], $fileList),
            fn($file) => !empty($file)
        )));

        if (empty($config['directory_list'])) {
            throw new \RuntimeException("Phan config has empty or invalid directory_list for plugin at: {$pluginRootPath}");
        }

        $exported = "<?php\n\nuse Phan\\Issue;\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($configPath, $exported);
    }


    /**
     * @param string $src
     * @param string $dst
     * @return void
     */
    private function copyDirectory(string $src, string $dst): void
    {
        if (!file_exists($dst)) {
            mkdir($dst, 0777, true);
        }

        foreach (scandir($src) as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;

            if (is_dir($srcFile)) {
                $this->copyDirectory($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
            }
        }
    }

    /**
     * @param string $destination
     * @return void
     */
    private function clonePlentySdkTo(string $destination): void
    {
        $sdkPath = rtrim($destination, '/') . '/plenty-plugin-interface';

        $command = "git clone --depth=1 --branch beta7 https://github.com/plentymarkets/plugin-interface.git $sdkPath";
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Failed to clone SDK: " . $process->getErrorOutput());
        }
    }
}
