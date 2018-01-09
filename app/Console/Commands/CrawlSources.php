<?php

namespace App\Console\Commands;

use App\Source;
use App\Utilties\CrawlerUtility;
use Exception;
use Illuminate\Console\Command;

class CrawlSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:sources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl random sources for 240 seconds.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
    	set_time_limit(300);
    	$start_time = microtime(true);
	    while (microtime(true) - $start_time < 240) {
		    $source = Source::orderBy('last_crawled', 'asc')->inRandomOrder()->first();
		    $crawler = new CrawlerUtility($source);
		    $this->info($crawler->crawl());
	    }
	    $this->info('Exiting after 240s');
	    return;
    }
}
