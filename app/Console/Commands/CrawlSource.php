<?php

namespace App\Console\Commands;

use App\Source;
use App\Utilties\CrawlerUtility;
use Exception;
use Illuminate\Console\Command;

class CrawlSource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:source';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl a random source.';

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
//    	$start_time = microtime(true);
//
//	    $run_time = $start_time - microtime(true);
//	    if ($run_time > 120) {
//		    $this->info('Exiting after 120s');
//		    break;
//	    }
//	    $source = Source::orderBy('last_crawled', 'asc')->inRandomOrder()->first();
	    $source = (new Source)->whereSiteId(13)->orderBy('last_crawled', 'asc')->inRandomOrder()->first();
	    if (!$source) {
	    	$this->info('No source found');
	    	return;
	    }
	    $crawler = new CrawlerUtility($source);
	    $this->info($crawler->crawl());
	    return;
    }
}
