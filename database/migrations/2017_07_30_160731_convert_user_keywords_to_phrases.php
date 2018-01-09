<?php

use App\Keyword;
use App\Phrase;
use App\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ConvertUserKeywordsToPhrases extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	$users = User::with('keywords')->get();
    	/** @var User $user */
	    foreach ($users as $user) {
    		/** @var Keyword $keyword */
		    foreach ($user->keywords as $keyword) {
		    	$kw = $keyword->keyword;
		    	/** @var Phrase $phrase */
		    	$phrase = Phrase::firstOrNew(['phrase' => $kw]);
		    	if (!$phrase->id) {
		    		$phrase->save();
				    $words = explode(' ', $kw);
				    if (count($words) > 1) {
					    foreach ($words as $word) {
						    $new = Keyword::firstOrCreate(['keyword' => $word]);
						    $phrase->keywords()->sync([$new->id], false);
					    }
				    } else {
					    $new = Keyword::firstOrCreate(['keyword' => $kw]);
					    $phrase->keywords()->sync([$new->id], false);
				    }
			    }
			    $user->phrases()->sync([$phrase->id], false);
		    }
	    }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
