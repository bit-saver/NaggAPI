<?php namespace App\Utilties;


use App\Site;
use App\Source;
use Exception;
use Log;

class SourceUtility {

	/**
	 * @param $url string
	 * @param $site Site
	 *
	 * @return int
	 */
	public static function create_sources($url, $site)
	{
		set_time_limit(300);
		try {
			$content = @file_get_contents($url);
		} catch (Exception $e) {
			Log::error('Error obtaining sources.');
			Log::error($e->getMessage());
			return 0;
		}

		if (!$content) {
			Log::error('Error getting content for: ' . $url);
			return 0;
		}
		// search for XML links
		preg_match_all( '/href=["\']?([^"\'>]+)["\']?/' , $content, $matches);
		if (!$matches || count($matches) < 2 || !count($matches[1])) {
			// perhaps it's gzipped?
			$content = gzdecode($content);
			preg_match_all( '/href=["\']?([^"\'>]+)["\']?/' , $content, $matches);
		}
		$sources = 0;
		foreach ($matches[1] as $url) {
			if (stripos($url, '//') === 0)
				$url = 'http:' . $url;
			if (!self::valid_url($url)) continue;
			if (stripos($url, 'video') !== false
				    || stripos($url, 'opensearch') !== false
				    || stripos($url, 'forbesreprints') !== false
				    || stripos($url, '/osd.xml') !== false
				    || stripos($url, 'manifest') !== false)
				continue;

			// Forbes doesn't have an RSS list but their sitemap can be used by appending /feed to each URL
			if (stripos($url, 'forbes.com') > 0 && stripos($url, '/feed') === false)
				$url = trim($url, '/') . '/feed';

			if (self::create_source($url, $site)) {
				Log::info('Created source for: ' . $url);
				$sources++;
			}
		}

		return $sources;
	}

	public static function create_sources_list($urls, $site)
	{
		set_time_limit(300);

		$sources = 0;
		foreach ($urls as $url) {
			if (stripos($url, '//') === 0)
				$url = 'http:' . $url;
			if (!self::valid_url($url)) continue;
			if (stripos($url, 'video') !== false
			    || stripos($url, 'opensearch') !== false
			    || stripos($url, 'forbesreprints') !== false
			    || stripos($url, '/osd.xml') !== false
			    || stripos($url, 'manifest') !== false)
				continue;

			// Forbes doesn't have an RSS list but their sitemap can be used by appending /feed to each URL
			if (stripos($url, 'forbes.com') !== false && stripos($url, '/feed') === false)
				$url = trim($url, '/') . '/feed';


			if (self::create_source($url, $site)) {
				Log::info('Created source for: ' . $url);
				$sources++;
			}
		}

		return $sources;

	}

	public static function create_source($url, $site)
	{
		set_time_limit(300);
		// TODO: Logging?
		$content = null;
		try {
			$content = @file_get_contents($url);
		} catch (Exception $e) {
			Log::error('Error creating source.');
			Log::error($e->getMessage());
			return false;
		}
		if (!$content) {
//			Log::info('Error creating source, no content: ' . $url);
			return false;
		}
		if (stripos($content, '<?xml') !== 0) return false;
		// this is valid xml
		file_put_contents('feed.xml', $content);
		try {
			$xml = @simplexml_load_string($content, null, LIBXML_NOCDATA);
		} catch (Exception $e) {
			Log::debug($e->getMessage());
			return false;
		}
		if (!isset($xml) || !$xml) return false;
		$source = Source::firstOrNew(['url' => $url]);
		if ($source->id)
			return false;
		$source->site_id = $site->id;
		if (isset($xml->channel->title))
			$source->title = strval($xml->channel->title);
		if (isset($xml->channel->description))
			$source->description = strval($xml->channel->description);
		return $source->save();
	}

	private static function valid_url($url) {
		return filter_var($url, FILTER_VALIDATE_URL);
	}
}