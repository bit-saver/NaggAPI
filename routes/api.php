<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/user/register', 'UserController@register');
Route::post('/user/login', 'UserController@login');
Route::post('/user/verify', 'UserController@verify');
Route::post('/user/resend', 'UserController@resend');


Route::group(['middleware' => ['jwt.auth', 'isVerified', 'role:admin']], function() {
	Route::get('/sites', 'SiteController@index');
	Route::post('/site/add', 'SiteController@add');
	Route::post('/source/add', 'SiteController@addSource');
	Route::post('/source/bulk', 'SiteController@bulk');
});

Route::group(['middleware' => ['jwt.auth', 'isVerified']], function()
{
	Route::post('/feed/articles', 'FeedController@articles');
	Route::post('/feed/hide', 'FeedController@hide');
	Route::post('/feed/save', 'FeedController@save');
	Route::post('/keyword/add', 'KeywordController@add');
	Route::post('/keyword/remove', 'KeywordController@remove');
	Route::post('/keyword/search', 'KeywordController@search');
	Route::post('/phrase/add', 'PhraseController@add');
	Route::post('/phrase/remove', 'PhraseController@remove');
});

Route::group(['middleware' => ['jwt.auth']], function()
{
	Route::get('/user/resend', 'UserController@resend');
	Route::get('/user/info', 'UserController@info');
});
