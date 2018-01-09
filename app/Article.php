<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Article
 *
 * @property int $id
 * @property int $source_id
 * @property string $url
 * @property string|null $title
 * @property string|null $media_url
 * @property string|null $media_title
 * @property string $published
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Source $source
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereMediaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereMediaUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article wherePublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereUrl($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Keyword[] $keywords
 * @property string|null $description
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereDescription($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 */
class Article extends Model
{

	protected $fillable = [
		'source_id',
		'url',
		'title',
		'media_url',
		'media_title',
		'published'
	];

	public function source()
	{
		return $this->belongsTo('App\Source');
	}

	public function keywords()
	{
		return $this->belongsToMany('App\Keyword')->withTimestamps();
	}

	public function users()
	{
		return $this->belongsToMany('App\User')->withPivot(['saved', 'hidden'])->withTimestamps();
	}
}
