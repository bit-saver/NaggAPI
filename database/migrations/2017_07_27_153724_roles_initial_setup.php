<?php

use App\Role;
use Illuminate\Database\Migrations\Migration;

class RolesInitialSetup extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$admin = new Role();
		$admin->name = 'admin';
		$admin->display_name = 'Administrator';
		$admin->save();
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
