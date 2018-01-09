<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Keyword
 *
 * @property int $id
 * @property string $keyword
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Keyword whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Keyword whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Keyword whereKeyword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Keyword whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Article[] $articles
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Phrase[] $phrases
 */
class Keyword extends Model
{

	protected $fillable = ['keyword'];

	public function articles()
	{
		return $this->belongstoMany('App\Article')->withTimestamps();
	}

	public function users()
	{
		return $this->belongstoMany('App\User')->withTimestamps();
	}

	public function phrases()
	{
		return $this->belongsToMany('App\Phrase');
	}

}
