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

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});

//Route::get('/user', function () {
//})->middleware('auth:api');

//Route::any('/test','Api\LoginController@index')->middleware('auth:api');
//Route::any('login','Api\LoginController@login')->middleware('auth:api');
//
Route::group([
    'middleware' => ['auth:api']
], function () {
    Route::any('/loginOut','Api\LoginController@logout');
    Route::any('/addEquipments','Api\LoginController@addEquipments');
    Route::any('/getUserEquipments','Api\LoginController@getUserEquipments');
    Route::any('/getEqMeterData','Api\LoginController@getEqMeterData');
    Route::any('/addData','Api\LoginController@addData');
});

Route::post('/login','Api\LoginController@token');
Route::post('/register','Api\LoginController@register');
Route::post('/getUsers','Api\LoginController@getUsers');
