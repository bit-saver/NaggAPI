<?php

namespace App\Http\Controllers;

use App\Site;
use App\Source;
use Auth;
use Illuminate\Http\Request;
use App\Utilties\SourceUtility;
use Log;

class SiteController extends Controller
{

	public function index()
	{
		return Site::withCount('sources')->orderBy('title', 'asc')->get();
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function add(Request $request)
	{
		$user = Auth::user();
		$title = $request->input('title');
		$domain = $request->input('domain');
		$url = $request->input('url');

		/** @var Site $site */
		$site = Site::firstOrNew(['domain' => $domain]);
		if (!$site->id) {
			$site->title = $title;
			$site->save();
		}
		$created = 0;
		if ($url)
			$created = SourceUtility::create_sources($url, $site);
		$user->sites()->sync([$site->id], false);
		return response()->json(['message' => 'Created ' . $created . ' new sources.']);
	}

	public function addSource(Request $request)
	{
		$site_id = $request->input('site_id');
		$url = $request->input('url');
		$site = Site::find($site_id);
		if (!$site)
			return response()->json(['error' => 'Site not found.']);
		$source = SourceUtility::create_source($url, $site);
		if ($source)
			return response()->json(['message' => 'Source created successfully.']);
		return response()->json(['error' => 'Error creating source.']);
	}

	public function bulk(Request $request)
	{
		$site_id = $request->input('site_id');
		$urls = $request->input('urls');
		$site = Site::find($site_id);
		if (!$site)
			return response()->json(['error' => 'Site not found.']);

		$created = 0;
		$urls = explode("\n", $urls);
		if (count($urls))
			$created = SourceUtility::create_sources_list($urls, $site);

		return response()->json(['message' => 'Created ' . $created . ' new sources.']);
	}
}
