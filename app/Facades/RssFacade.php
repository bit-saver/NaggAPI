<?php namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class RssFacade extends Facade {

	protected static function getFacadeAccessor() {
		return 'Rss';
	}

}
