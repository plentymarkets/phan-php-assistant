<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CompatibilityCheckerService;

class CheckCompatibilityCommand extends Command
{
    protected $signature = 'check:compatibility {--path= : Path to plugin(s) directory}';
    protected $description = 'Check PHP 8.2 compatibility for plugin(s)';


    public function handle(): void
    {
        $checkerService = app(CompatibilityCheckerService::class);
        $path = $this->option('path');

        if (!$path || !is_dir($path)) {
            $this->error('Invalid or missing path.');
            return;
        }

        $results = $checkerService->check($path);
        foreach ($results as $plugin => $result) {
            $this->line("\n==== [$plugin] ====");
            $this->line($result['success'] ? "✅ Compatible" : "❌ Incompatible");

            if (is_array($result['output'])) {
                $this->line(implode("\n", $result['output']));
            } elseif (is_string($result['output']) && $result['output'] !== '') {
                $this->line($result['output']);
            }

            if (!$result['success'] && !empty($result['errorOutput'])) {
                $this->error(trim($result['errorOutput']));
            }
        }
    }
}
