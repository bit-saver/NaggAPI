<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Source
 *
 * @property int $id
 * @property int $site_id
 * @property string $url
 * @property string|null $title
 * @property string|null $description
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Site $site
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Source whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Source whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Source whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Source whereSiteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Source whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Source whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Source whereUrl($value)
 * @mixin \Eloquent
 * @property string|null $last_crawled
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Source whereLastCrawled($value)
 */
class Source extends Model
{

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'url'
	];

	public function site()
	{
		return $this->belongsTo('App\Site');
	}

	public function articles()
	{
		return $this->hasMany('App\Article');
	}
}
