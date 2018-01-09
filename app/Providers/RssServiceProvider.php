<?php namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Utility\RssUtility;

class RssServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	public function boot() {
//		$this->publishes([
//			__DIR__ . '/config/feeds.php' => config_path('feeds.php'),
//		]);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->singleton('Rss', function () {
			$config = config('rss');

			if (!$config) {
				throw new \RunTimeException('Rss configuration not found. ');
			}

			return new RssUtility($config);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return ['Rss'];
	}

}
