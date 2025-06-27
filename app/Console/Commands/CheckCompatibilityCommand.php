<?php

namespace App\Console\Commands;

use App\Support\PathResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Services\CompatibilityCheckerService;

class CheckCompatibilityCommand extends Command
{
    protected $signature = 'check:compatibility {--withRector : Run Rector analysis after Phan}';
    protected $description = 'Check PHP 8.2 compatibility for plugin(s)';

    public function handle(): void
    {
        $checkerService = app(CompatibilityCheckerService::class);
        $path = PathResolver::resolve();

        $results = $checkerService->check($path);

        $allSuccessful = true;

        foreach ($results as $plugin => $result) {
            $this->line("\n==== [$plugin] ====");
            $this->line($result['success'] ? "✅ Compatible" : "❌ Incompatible");

            if (is_array($result['output'])) {
                $this->line(implode("\n", $result['output']));
            } elseif (is_string($result['output']) && $result['output'] !== '') {
                $this->line($result['output']);
            }

            if (!$result['success']) {
                $allSuccessful = false;
            }
        }

        if ($this->option('withRector')) {
            if ($allSuccessful) {
                $this->info("\n--- All plugins passed. Running Rector analysis... ---");

                $code = Artisan::call('check:refactor');

                $this->line(Artisan::output());

                if ($code !== 0) {
                    $this->warn('Rector finished with warnings or errors.');
                }
            } else {
                $this->warn("\nRector analysis skipped because one or more plugins failed the Phan check.");
            }
        }
    }
}
