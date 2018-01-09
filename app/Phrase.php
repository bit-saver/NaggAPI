<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Phrase
 *
 * @property int $id
 * @property string $phrase
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Phrase whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Phrase whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Phrase wherePhrase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Phrase whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Keyword[] $keywords
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 */
class Phrase extends Model
{
	protected $fillable = ['phrase'];

	public function keywords()
	{
		return $this->belongsToMany('App\Keyword');
	}

	public function users()
	{
		return $this->belongsToMany('App\User');
	}
}
