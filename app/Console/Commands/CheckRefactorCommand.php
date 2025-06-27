<?php

namespace App\Console\Commands;

use App\Support\PathResolver;
use Illuminate\Console\Command;
use App\Services\RectorRefactorService;

class CheckRefactorCommand extends Command
{
    protected $signature = 'check:refactor';
    protected $description = 'Run Rector refactor suggestions for plugin(s)';

    public function handle(): void
    {
        $path = PathResolver::resolve();

        /** @var RectorRefactorService $service */
        $service = app(RectorRefactorService::class);
        $results = $service->run($path);

        foreach ($results as $plugin => $result) {
            $this->line("\n==== [$plugin] ====");
            $this->line($result['success'] ? "✅ Rector completed successfully" : "❌ Rector failed");

            if (!empty($result['output'])) {
                $this->line($result['output']);
            }

            if (!$result['success'] && !empty($result['errorOutput'])) {
                $this->error(trim($result['errorOutput']));
            }
        }
    }
}
