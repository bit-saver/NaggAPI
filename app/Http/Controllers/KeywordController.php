<?php

namespace App\Http\Controllers;

use App\Keyword;
use Auth;
use Illuminate\Http\Request;
use Log;


class KeywordController extends Controller
{
	public function add(Request $request)
	{
		$user = Auth::user();
		$keyword_id = $request->input('keyword_id');
		$keyword_name = $request->input('keyword');
		if ($keyword_id)
			$keyword = Keyword::findOrFail($keyword_id);
		else
			$keyword = Keyword::firstOrCreate(['keyword' => $keyword_name]);
		$user->keywords()->sync([$keyword->id], false);
		return $keyword;
	}

	public function remove(Request $request)
	{
		$user = Auth::user();
		$keyword_id = $request->input('keyword_id');
		Log::info('removing keyword: ' . $keyword_id);
		$user->load(['phrases' => function($phrases) use ($keyword_id) {
			$phrases->whereHas('keywords', function($keywords) use ($keyword_id) {
				$keywords->where('keyword_id', '=', $keyword_id);
			});
		}]);
		foreach ($user->phrases as $phrase) {
			$user->phrases()->detach([$phrase->id]);
		}
		return ['message' => 'Successfully removed keyword.'];
	}

	public function search(Request $request)
	{
		$keyword = $request->input('keyword');
		$keywords = Keyword::where('keyword', 'like', "$keyword%")->take(20)->get();
		return $keywords;
	}
}
