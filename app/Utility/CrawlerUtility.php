<?php namespace App\Utilties;


use App\Article;
use App\Keyword;
use App\Site;
use App\Source;
use Carbon\Carbon;
use Exception;
use Log;
use Rss;
use SimplePie_Item;

class CrawlerUtility {
	
	public $source;

	/**
	 * CrawlerUtility constructor.
	 *
	 * @param $source Source
	 */
	public function __construct($source)
	{
		$this->source = $source;
	}

	
	public function crawl()
	{
		ini_set('memory_limit', '1024M');
		ob_start();
		try {
			$this->info('Attempting to parse: ' . $this->source->url);
			$str = file_get_contents($this->source->url);
			file_put_contents('feed.xml', $str);

			$rss = Rss::make($this->source->url);
			if (!$rss) {
				$this->info('Feed could not be created.');
				return ob_get_clean();
			}
			/** @var SimplePie_Item $item */
			foreach ($rss->get_items() as $item) {
				$link = $item->get_permalink();
				$pubDate = $item->get_date('Y-m-d H:i:s');
				$title = $item->get_title();
				// convert & first in case they encoded that which would break the html entity code
				$title = str_replace('&amp;', '&', $title);
				$title = htmlspecialchars_decode(html_entity_decode(trim($title),ENT_QUOTES, 'ISO-8859-1'));
				$description = trim(str_replace('&nbsp;', ' ', strip_tags($item->get_description())));
				// convert & first in case they encoded that which would break the html entity code
				$description = str_replace('&amp;', '&', $description);
				$description = htmlspecialchars_decode(html_entity_decode(trim($description),ENT_QUOTES, 'ISO-8859-1'));

				$media_image_url = null;
				$media_title = null;
				/** @var \SimplePie_Enclosure $enc */
				foreach ($item->get_enclosures() as $enc) {
					if ((stripos($enc->type, 'image') !== false || 'image' === $enc->get_medium()) && $enc->get_link()) {
						$media_image_url = $enc->get_link();
						if ($enc->get_title())
							$media_title = trim(str_replace('&nbsp;', ' ', urldecode($enc->get_title())));
						break;
					}
				}
				$thumbnails = $item->get_thumbnail();
				if (!$media_image_url && $thumbnails && count($thumbnails) > 0)
					$media_image_url = reset($thumbnails);

				$keywords = [];
				try {
					$content = file_get_contents($link);
					if (!mb_check_encoding($content, 'UTF-8'))
						$content = gzdecode($content);
					file_put_contents('article.html', $content);

					$meta = $this->get_meta($content);

					if (array_key_exists('og:url', $meta))
						$link = $meta['og:url'];

					$image = null;
					if (array_key_exists('og:image', $meta)) {
						$image = $meta['og:image'];
						if ($image && self::valid_url($image))
							$media_image_url = $image;
					}
					$keywords = $this->get_meta_keywords($meta);

					if (!$pubDate && array_key_exists('date', $meta)) {
						$this->info('Getting content date because: ' . $pubDate);
						$pubDate = $meta['date'];
						if ($pubDate) $pubDate = date('Y-m-d H:i:s', strtotime($pubDate));
					}
					if (!$pubDate && array_key_exists('datepublished', $meta)) {
						$this->info('Getting content date because: ' . $pubDate);
						$pubDate = $meta['datepublished'];
						if ($pubDate) $pubDate = date('Y-m-d H:i:s', strtotime($pubDate));
					}
					if (!$pubDate && array_key_exists('article:published', $meta)) {
						$this->info('Getting content date because: ' . $pubDate);
						$pubDate = $meta['article:published'];
						if ($pubDate) $pubDate = date('Y-m-d H:i:s', strtotime($pubDate));
					}
				} catch (Exception $e) {
					$this->info($e->getMessage());
				}
				$this->info("Trying to find article for: \n" . $link . "\n");
				/** @var Article $article */
				$article = Article::firstOrNew(['url' => $link]);
				if ($article->id) {
					$this->info('Article already exists, updating');
//					continue;
				};
				$article->source_id = $this->source->id;
				$article->title = $title;
				$article->description = $description;
				$article->published = date('Y-m-d H:i:s', strtotime($pubDate));
				$article->media_url = $media_image_url;
				$article->media_title = $media_title;
				$article->save();

				if (!$keywords || !count($keywords)) {
					$this->info('No keywords, using title and description as alternates.');
					$keywords = $this->get_keywords_alt($title, $description);
				}

				if ($keywords && count($keywords)) {
					foreach ($keywords as $kw) {
						$kw = trim($kw);
						if ($kw == 'meta' || $kw == 'keyword' || $kw == 'keywords') continue;
//						$this->info('Creating keyword: ' . $kw);
						/** @var Keyword $keyword */
						$keyword = Keyword::firstOrCreate(['keyword' => $kw]);
						$article->keywords()->sync([$keyword->id], false);
					}
				}
			}

		} catch (Exception $e) {
			$this->info($e->getMessage());
		}
		$this->source->last_crawled = Carbon::now();
		$this->source->save();
		return ob_get_clean();
	}

	public function crawl_old()
	{
		ob_start();
		try {
			$this->info('Attempting to parse: ' . $this->source->url);
			$str = file_get_contents($this->source->url);
			file_put_contents('feed.xml', $str);

			$rss = simplexml_load_file($this->source->url, null, LIBXML_NOCDATA);

			foreach($rss->channel->item as $item) {
				$title = (string) $item->title;
				$link = (string) $item->link;
				if (strpos($link, '?') !== false)
					$link = substr($link, 0, strpos($link, '?'));
				$description = (string) $item->description;
//				$description = trim(preg_replace( "/^[^A-Za-z0-9]+/", '', str_replace("&nbsp;", " ", strip_tags(htmlspecialchars_decode(html_entity_decode($description,ENT_QUOTES, 'ISO-8859-1'))))));

				$description = strip_tags(htmlspecialchars_decode(html_entity_decode($description,ENT_QUOTES|ENT_HTML5, 'ISO-8859-1')));
				$description = preg_replace_callback("/(&#[0-9]+;)/", function($m) { return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES"); }, $description);
				$description = trim(str_replace("&nbsp;", " ", $description));
				$description = preg_replace( "/^[^A-Za-z0-9]+/", '', $description);
				$pubDate = (string) $item->pubDate;

				$this->info('Getting media');
				$media_image_url = $this->get_media($item);
				$media_title = '';
				$media = $item->children('media', true);
				if ($media && $media->description)
					$media_title = (string) $media->description;

				$keywords = [];
				try {
					$content = file_get_contents($link);
					file_put_contents('article.html', $content);

					$content_url = $this->get_content_url($content);
					if ($content_url)
						$link = $content_url;

					$image = $this->get_content_image($content);
					if ($image) {
						$this->info('got content image: ' . $image);
						$media_image_url = $image;
					}
					$keywords = $this->get_content_keywords($content);

					if (!$pubDate)
						$pubDate = $this->get_content_date($content);
				} catch (Exception $e) {
					$this->info($e->getMessage());
				}
				$this->info("Trying to find article for: \n" . $link . "\n");
				/** @var Article $article */
				$article = Article::firstOrNew(['url' => $link]);
				if ($article->id) {
					$this->info('Article already exists');
					continue;
				};
				$article->source_id = $this->source->id;
				$article->title = $title;
				$article->description = $description;
				$article->published = date('Y-m-d H:i:s', strtotime($pubDate));
				$article->media_url = $media_image_url;
				$article->media_title = $media_title;
				$article->save();

				if ($keywords && count($keywords)) {
					foreach ($keywords as $kw) {
						$kw = trim($kw);
						$this->info('Creating keyword: ' . $kw);
						/** @var Keyword $keyword */
						$keyword = Keyword::firstOrCreate(['keyword' => $kw]);
						$article->keywords()->sync([$keyword->id], false);
					}
				}
			}

		} catch (Exception $e) {
			$this->info($e->getMessage());
		}
		$this->source->last_crawled = Carbon::now();
		$this->source->save();
		return ob_get_clean();
	}

	private function info($str)
	{
//		file_put_contents(storage_path('/logs/crawler.log'), print_r($str, 1) . "\r\n", FILE_APPEND);
		echo print_r($str,1) . "\r\n";
	}


	private function get_meta($content)
	{
		$pattern = '
            ~<\s*meta\s
            
            # using lookahead to capture type to $1
            (?=[^>]*?
            \b(?:name|property|http-equiv)\s*=\s*
            (?|"\s*([^"]*?)\s*"|\'\s*([^\']*?)\s*\'|
            ([^"\'>]*?)(?=\s*/?\s*>|\s\w+\s*=))
            )
            
            # capture content to $2
            [^>]*?\bcontent\s*=\s*
            (?|"\s*([^"]*?)\s*"|\'\s*([^\']*?)\s*\'|
            ([^"\'>]*?)(?=\s*/?\s*>|\s\w+\s*=))
            [^>]*>
            
            ~ix';

		if (preg_match_all($pattern, $content, $out)) {
			$metas = array_combine($out[1], $out[2]);
			$ret = array();
			foreach ($metas as $key => $val)
				$ret[strtolower($key)] = $val;
			return $ret;
		}
		return array();
	}

	private function get_meta_keywords($meta)
	{
		$ret = array();
		$exclude = $this->stopwords();
		$matches = '';
		if (array_key_exists('keywords', $meta))
			$matches .= $meta['keywords'] . ' ';
		if (array_key_exists('news_keywords', $meta))
			$matches .= $meta['news_keywords'];
		$words = htmlspecialchars_decode(html_entity_decode(strtolower(preg_replace("/[\s]+/i", ' ', trim($matches))),ENT_QUOTES, 'ISO-8859-1'));
		$words = preg_replace("/[\'\\\"]+/", '', $words);
		$words = preg_replace("/[^a-zA-Z\s]+/", ' ', $words);
		foreach (explode(' ', $words) as $word)
			if (!empty($word) && strlen($word) > 1 && !in_array($word, $exclude) && !in_array($word, $ret))
				$ret[] = $word;
		$this->info(implode(', ', $ret));
		return $ret;
	}

	private function get_keywords_alt($title, $description)
	{

		$title = htmlspecialchars_decode(html_entity_decode(strtolower(preg_replace("/[\s]+/i", ' ', trim($title))),ENT_QUOTES, 'ISO-8859-1'));
		$title = preg_replace("/[\'\\\"]+/", '', $title);
		$description = htmlspecialchars_decode(html_entity_decode(strtolower(preg_replace("/[\s]+/i", ' ', trim($description))),ENT_QUOTES, 'ISO-8859-1'));
		$description = preg_replace("/[\'\\\"]+/", '', $description);
		$words = preg_replace("/[^a-zA-Z\s]+/", ' ', $title . ' ' . $description);
		$exclude = $this->stopwords();
		$keywords = [];
		foreach (explode(' ', $words) as $word)
			if (!empty($word) && strlen($word) > 1 && !in_array($word, $exclude) && !in_array($word, $keywords))
				$keywords[] = $word;
		return $keywords;
	}


	private function get_media($item)
	{
		$media = $item->children('media', true);
		if ($media) {
			if ($media->group) {
				foreach ($media->group->content as $content) {
					$attrs = $content->attributes();
					if ((string) $attrs->url && (stripos((string) $attrs->url, 'rcom-default') === false) && (!$attrs->medium || ((string) $attrs->medium) == 'image'))
						return (string) $attrs['url'];
				}
			} else if ($media->content) {
				return (string) $media->content->attributes()->url;
			} else if ($media->thumbnail) {
				return (string) $media->thumbnail->attributes()->url;
			}
		} else if (isset($item->enclosure)) {
			return (string) $item->enclosure->attributes()->url;
		}
		return null;
	}

	private static function stopwords()
	{
		return array("meta", "keywords", "keyword", "a", "about", "above", "above", "across", "after", "afterwards", "again", "against", "all", "almost", "alone", "along", "already", "also","although","always","am","among", "amongst", "amoungst", "amount",  "an", "and", "another", "any","anyhow","anyone","anything","anyway", "anywhere", "are", "around", "as",  "at", "back","be","became", "because","become","becomes", "becoming", "been", "before", "beforehand", "behind", "being", "below", "beside", "besides", "between", "beyond", "bill", "both", "bottom","but", "by", "call", "can", "cannot", "cant", "co", "con", "could", "couldnt", "cry", "de", "describe", "detail", "do", "done", "down", "due", "during", "each", "eg", "eight", "either", "eleven","else", "elsewhere", "empty", "enough", "etc", "even", "ever", "every", "everyone", "everything", "everywhere", "except", "few", "fifteen", "fify", "fill", "find", "fire", "first", "five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further", "get", "give", "go", "had", "has", "hasnt", "have", "he", "hence", "her", "here", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "him", "himself", "his", "how", "however", "hundred", "ie", "if", "in", "inc", "indeed", "interest", "into", "is", "it", "its", "itself", "keep", "last", "latter", "latterly", "least", "less", "ltd", "made", "many", "may", "me", "meanwhile", "might", "mill", "mine", "more", "moreover", "most", "mostly", "move", "much", "must", "my", "myself", "name", "namely", "neither", "never", "nevertheless", "next", "nine", "no", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "on", "once", "one", "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out", "over", "own","part", "per", "perhaps", "please", "put", "rather", "re", "same", "see", "seem", "seemed", "seeming", "seems", "serious", "several", "she", "should", "show", "side", "since", "sincere", "six", "sixty", "so", "some", "somehow", "someone", "something", "sometime", "sometimes", "somewhere", "still", "such", "system", "take", "ten", "than", "that", "the", "their", "them", "themselves", "then", "thence", "there", "thereafter", "thereby", "therefore", "therein", "thereupon", "these", "they", "thickv", "thin", "third", "this", "those", "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "top", "toward", "towards", "twelve", "twenty", "two", "un", "under", "until", "up", "upon", "us", "very", "via", "was", "we", "well", "were", "what", "whatever", "when", "whence", "whenever", "where", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "whoever", "whole", "whom", "whose", "why", "will", "with", "within", "without", "would", "yet", "you", "your", "yours", "yourself", "yourselves", "the");
	}

	private static function valid_url($url) {
		return filter_var($url, FILTER_VALIDATE_URL);
	}
}