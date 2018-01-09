<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Rss;
use SimplePie_Item;

class TestFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:feed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test RSS XML parser';

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
    	ini_set('memory_limit', '2048M');
    	$url = 'http://feeds.washingtonpost.com/rss/rss_blogpost';
    	$url = 'http://feeds.reuters.com/reuters/environment';
    	$url = 'http://www.nytimes.com/services/xml/rss/nyt/YourMoney.xml';
    	$url = 'http://rss.cnn.com/rss/cnn_allpolitics.rss';
//    	$url = 'http://www.huffingtonpost.com/feeds/verticals/comedy/blog.xml';
//    	$url = 'http://feeds.feedburner.com/TechCrunch/Groupon';
    	file_put_contents('feed.xml', file_get_contents($url));
//	    $feed = Feeds::make('http://feeds.feedburner.com/TechCrunch/Groupon');
	    $feed = Rss::make($url);
	    file_put_contents('feed.txt', '');
	    $title = $feed->get_title();
	    $desc = $feed->get_description();
	    $this->p($title);
	    $this->p($desc);
	    $items = $feed->get_items();
	    /** @var SimplePie_Item $item */
	    foreach ($items as $item) {
	    	$obj = new \stdClass();
	    	$obj->link = $item->get_permalink();
	    	$obj->title = $item->get_title();
	    	$obj->desc = trim(strip_tags($item->get_description()));
	    	$obj->date = $item->get_date('Y-m-d H:i:s');
	    	$obj->image = null;
	    	$obj->image_title = null;
//	    	$obj->enc = $item->get_enclosures();
	    	/** @var \SimplePie_Enclosure $enc */
		    foreach ($item->get_enclosures() as $enc) {
	    		if ((stripos($enc->type, 'image') !== false || 'image' === $enc->get_medium()) && $enc->get_link()) {
				    $obj->image = $enc->get_link();
				    if ($enc->get_title())
				    	$obj->image_title = trim(str_replace('&nbsp;', ' ', urldecode($enc->get_title())));
				    break;
			    }
		    }
		    if (!$obj->image && count($item->get_thumbnail()) > 0)
	            $obj->image = reset($item->get_thumbnail());
	    	$this->p($obj);
	    }
	    return null;
    }

    private function p($str) {
	    file_put_contents('feed.txt', print_r($str,1) . "\r\n", FILE_APPEND);
    }
}
