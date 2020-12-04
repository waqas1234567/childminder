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

Route::post('/registerUser', 'api\apiController@store');
Route::post('/loginUser', 'api\apiController@login');
Route::post('/forgetPassword', 'api\apiController@forgetPassword');
Route::post('/forgetPasswords', 'api\apiController@forgetPasswords');

Route::post('/resetPassword', 'api\apiController@resetPassword');
Route::post('/verifyUser', 'api\apiController@verifyUser');
Route::post('/resendCode', 'api\apiController@resendCode');
Route::middleware('auth:api')->post('/addEmergencyContact', 'api\apiController@addEmergencyContact');
Route::middleware('auth:api')->post('/updateEmergencyContact', 'api\apiController@updateEmergencyContact');

Route::middleware('auth:api')->post('/sendRequest', 'api\apiController@sendRequest');
Route::middleware('auth:api')->post('/addChildData', 'api\apiController@addChildData');
Route::middleware('auth:api')->post('/updateChildData', 'api\apiController@updateChildData');

Route::middleware('auth:api')->get('/getChildData/{id}', 'api\apiController@getChildData');
Route::middleware('auth:api')->get('/getContacts/{id}', 'api\apiController@getContacts');
Route::post('/guestLogin', 'api\apiController@guestLogin');
Route::middleware('auth:api')->post('/updateProfileStatus', 'api\apiController@updateProfileStatus');
Route::middleware('auth:api')->get('/getProfileStatus/{id}', 'api\apiController@getProfileStatus');
Route::middleware('auth:api')->post('/logout', 'api\apiController@logout');
Route::middleware('auth:api')->get('/getNotifications/{id}', 'api\apiController@getNotifications');



