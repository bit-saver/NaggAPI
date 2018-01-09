<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
	public function up()
	{
		Schema::create('articles', function (Blueprint $table) {
			$table->increments('id');
			$table->integer('source_id')->unsigned()->index();
			$table->foreign('source_id')->references('id')->on('sources')->onDelete('cascade');
			$table->string('url')->unique();
			$table->string('title')->nullable();
			$table->string('description')->nullable();
			$table->string('media_url')->nullable();
			$table->string('media_title')->nullable();
			$table->dateTime('published');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('articles');
	}
}
