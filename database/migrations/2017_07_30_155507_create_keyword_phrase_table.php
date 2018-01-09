<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKeywordPhraseTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('keyword_phrase', function (Blueprint $table) {
			$table->integer('phrase_id')->unsigned()->index();
			$table->foreign('phrase_id')->references('id')->on('phrases')->onDelete('cascade');

			$table->integer('keyword_id')->unsigned()->index();
			$table->foreign('keyword_id')->references('id')->on('keywords')->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('keyword_phrase');
	}
}
