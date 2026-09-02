<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Feed;
use App\Services\FeedGeneratorService;

class GenerateFeedsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feeds:generate {--feed= : Optional specific feed ID to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate product feeds (e.g., Facebook CSV) based on feed configurations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $feedId = $this->option('feed');
        $feeds = $feedId ? Feed::where('id', $feedId)->get() : Feed::where('is_active', true)->get();

        if ($feeds->isEmpty()) {
            $this->info('No feeds to generate.');
            return 0;
        }

        $service = app(FeedGeneratorService::class);
        foreach ($feeds as $feed) {
            $url = $service->generate($feed);
            $this->info("Feed {$feed->name} generated: {$url}");
        }
        return 0;
    }
}
?>
