<?php

namespace App\Http\Controllers;

use App\Keyword;
use App\Phrase;
use Auth;
use Illuminate\Http\Request;


class PhraseController extends Controller
{
	public function add(Request $request)
	{
		$user = Auth::user();
		$keyword_id = $request->input('keyword_id');
		$phrase_name = $request->input('phrase');
		//TODO: Sanitize phrase
		$phrase_name = preg_replace("/[^a-zA-Z\s]+/", '', $phrase_name);
		if (!$keyword_id && strlen($phrase_name) < 2)
			return response()->json(['error' => 'Phrase too short.']);
		$phrase = null;
		if ($keyword_id) {
			/** @var Keyword $keyword */
			$keyword = Keyword::findOrFail($keyword_id);
			/** @var Phrase $phrase */
			$phrase = Phrase::firstOrNew(['phrase' => $keyword->keyword]);
			if (!$phrase->id) {
				$phrase->save();
				$phrase->keywords()->attach($keyword_id);
			}
		} else {
			/** @var Phrase $phrase */
			$phrase = Phrase::firstOrNew(['phrase' => $phrase_name]);
			if (!$phrase->id) {
				$phrase->save();
				$words = explode(' ', $phrase_name);
				foreach ($words as $word) {
					if (strlen($word) < 2) continue;
					$kw = Keyword::firstOrCreate(['keyword' => $word]);
					$phrase->keywords()->attach($kw->id);
				}
			}
		}

		if ($phrase)
			$user->phrases()->sync([$phrase->id], false);
		return $phrase;
	}

	public function remove(Request $request)
	{
		$user = Auth::user();
		$phrase_id = $request->input('phrase_id');
		$phrase = Phrase::withCount('keywords')->withCount('users')->findOrFail($phrase_id);
		$user->phrases()->detach($phrase_id);
		if (!$phrase->users_count)
			$phrase->delete();
		return ['message' => 'Successfully removed keyword.'];
	}

	public function search(Request $request)
	{
		$keyword = $request->input('keyword');
		$keywords = Keyword::where('keyword', 'like', "%$keyword%")->get();
		return $keywords;
	}
}
