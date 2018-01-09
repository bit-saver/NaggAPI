<?php

namespace App\Http\Controllers;

use App\Article;
use App\Keyword;
use App\Phrase;
use App\Site;
use Auth;
use DB;
use Illuminate\Http\Request;
use Log;

class FeedController extends Controller
{

	public function articles(Request $request)
	{
		$user = Auth::user()->load('phrases.keywords');
		$filter = $request->input('filter');
		$phrases = $user->phrases;

		$map = [];
		/** @var Phrase $phrase */
		foreach ($phrases as $phrase) {
			$kws = [];
			/** @var Keyword $keyword */
			foreach ($phrase->keywords as $keyword) {
				$kws[$keyword->id] = [
					'phrase_id' => $phrase->id,
					'keyword_ids' => [$keyword->id]
				];
			}
			foreach ($kws as $keyword_id => $kw) {
				$kw['keyword_ids'] = array_values(array_diff(array_keys($kws), $kw['keyword_ids']));
				if (array_key_exists($keyword_id, $map))
					$map[$keyword_id][] = $kw;
				else
					$map[$keyword_id] = [$kw];
			}
		}

		$page_args = $request->only(['page', 'per_page', 'sort', 'order', 'rand']);
		$page = $page_args['page'] ?: 1;
		if ($page === 1)
			$seed = rand(1,9999);
		else
			$seed = $page_args['rand'];
		$per_page = 20;
		$articles = Article::with(['keywords','source.site']);

		$has_filter = false;
		$include = [];
		$exclude = null;
		if ($filter['keywords']) {
			$has_filter = true;
			/** @var Phrase $phrase */
			foreach ($phrases as $phrase) {
				$keyword_ids = $phrase->keywords->pluck('id')->toArray();
				if (!count($keyword_ids)) continue;
				$article_ids = DB::select("SELECT article_id, COUNT(keyword_id) as c FROM `article_keyword` WHERE keyword_id IN (" . implode(',', $keyword_ids) . ") GROUP BY article_id  HAVING c = " . count($keyword_ids));
				$ids = array_map(function ($object) { return $object->article_id; }, $article_ids);
//				foreach ($phrase->keywords as $keyword) {
//					if (!$ids) {
//						$ids = Article::whereHas('keywords', function($query) use ($keyword) {
//							$query->where('keyword_id', '=', $keyword->id);
//						});
//					} else {
//						$ids = $ids->whereHas('keywords', function($query) use ($keyword) {
//							$query->where('keyword_id', '=', $keyword->id);
//						});
//					}
//				}
//				$ids = $ids->select('id')->get()->pluck('id')->toArray();
				$include = array_merge($include, $ids);
				$include = array_unique($include);
			}
		}
		if ($filter['saved']) {
			$has_filter = true;
			$saved = $user->articles()->wherePivot('saved', 1)->select('article_id')->get()->pluck('article_id')->toArray();
			// we want ONLY saved articles
			$include = array_diff($saved, $include);
		}
		if ($filter['hidden']) {
			$has_filter = true;
			$hidden = $user->articles()->wherePivot('hidden', 1)->select('article_id')->get()->pluck('article_id')->toArray();
			// we want ONLY hidden articles (for the hidden page)
			$include = array_diff($hidden, $include);
			$hidden = [];
		} else {
			$hidden = $user->articles()->wherePivot('hidden', 1)->select('article_id')->get()->pluck('article_id')->toArray();
			// hide hidden articles for non-hidden pages
			$exclude = $hidden;
		}

		if ($has_filter) { // if has filter is true, this means we want to subtract the hidden articles from the include list
			$include = array_unique(array_diff($include, $hidden));
			$articles = $articles->whereIn('id', $include);
		} else if ($exclude) // otherwise, we simply get all articles and subtract hidden from that
			$articles = $articles->whereNotIn('id', $hidden);

		$articles = $articles->with(['users' => function ($query) use ($user) {
			$query->where('user_id', '=', $user->id);
			$query->select(['saved','hidden']);
		}]);
		$articles = $articles->orderBy('published', 'desc')->orderBy(DB::raw("RAND($seed)"))->paginate($per_page, ['*'], 'page', $page);
		return ['articles' => $articles, 'user_phrases' => $phrases, 'keyword_map' => $map, 'rand' => $seed];
	}

	public function articles_old(Request $request)
	{
		$user = Auth::user();
		$filter = $request->input('filter');
		$keywords = $user->keywords;

		$klinks = [];
		foreach ($keywords as $keyword) {
			$words = explode(' ', $keyword->keyword);
			if (count($words) < 2) continue;
			$items = [];
			foreach ($words as $word) {
				$item = Keyword::whereKeyword(trim($word))->first();
				if ($item) $items[] = $item;
			}
			foreach ($items as $item) {
				$list = [];
				foreach ($items as $link)
					if ($link->id != $item->id)
						$list[] = $link->id;
				if (array_key_exists($item->id, $klinks))
					$klinks[$item->id] = [];
				$klinks[$item->id][] = $list;
			}
		}
		$page_args = $request->only(['page', 'per_page', 'sort', 'order']);
		$page = $page_args['page'] ?: 1;
		$per_page = 20;
		$articles = Article::with(['keywords','source.site']);
		if ($filter['hidden']) {
			$hidden = $user->articles()->wherePivot('hidden', 1)->select('article_id')->get()->pluck('article_id')->toArray();
			$articles = $articles->whereIn('id', $hidden);
		} else {
			$hidden = $user->articles()->wherePivot('hidden', 1)->select('article_id')->get()->pluck('article_id')->toArray();
			$articles = $articles->whereNotIn('id', $hidden);
		}
		if ($filter['saved']) {
			$saved = $user->articles()->wherePivot('saved', 1)->select('article_id')->get()->pluck('article_id')->toArray();
			$articles = $articles->whereIn('id', $saved);
		}
		if ($filter['keywords']) {
			$first_where = true;
			foreach ($keywords as $keyword) {
				if (!$first_where) {
					$articles = $articles->orWhere(function ($query) use ($keyword) {
						$kw = $keyword->keyword;
//						$query->whereHas('keywords', function ($subquery) use ($kw) {
//							$subquery->where('keyword', '=', $kw);
//						});
						$words = explode( ' ', $kw );
						foreach ($words as $word) {
							$query->whereHas('keywords', function ($subquery) use ($word) {
								$subquery->where('keyword', '=', $word);
							});
						}
					});
				} else {
					$first_where = false;
					$articles = $articles->where(function ($query) use ($keyword) {
						$kw = $keyword->keyword;
//						$query->whereHas('keywords', function ($subquery) use ($kw) {
//							$subquery->where('keyword', '=', $kw);
//						});
						$words = explode( ' ', $kw );
						foreach ($words as $word) {
							$query->whereHas('keywords', function ($subquery) use ($word) {
								$subquery->where('keyword', '=', $word);
							});
						}
					});
				}
			}
		}
		$articles = $articles->with(['users' => function ($query) use ($user) {
			$query->where('user_id', '=', $user->id);
			$query->select(['saved','hidden']);
		}]);
		$articles = $articles->orderBy('published', 'desc')->inRandomOrder()->paginate($per_page, ['*'], 'page', $page);
		return ['articles' => $articles, 'user_keywords' => $keywords, 'user_klinks' => $klinks];
	}

	public function hide(Request $request)
	{
		$user = Auth::user();
		$article_id = $request->input('article_id');
		$hidden = $request->input('hidden');
		$user->articles()->sync([$article_id => ['hidden' => $hidden]], false);
		return ['message' => 'Article ' . ($hidden ? 'hidden' : 'shown') . '.'];
	}

	public function save(Request $request)
	{
		$user = Auth::user();
		$article_id = $request->input('article_id');
		$saved = $request->input('saved');
		$user->articles()->sync([$article_id => ['saved' => $saved]], false);
		return ['message' => 'Article ' . ($saved ? 'saved' : 'unsaved') . '.'];
	}

}
