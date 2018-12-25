<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::get('ajax', function(){ return view('ajax'); });
Route::post('/set-as-known','AjaxController@post');
Route::post('/delete-word','AjaxController@delete');
Route::post('/import-file','HomeController@import');
Route::post('/change-translation','AjaxController@changeTranslation');
