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

use LaravelFCM\Message\Topics;
use Twilio\Rest\Client;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Facades\FCM;

Auth::routes();
Route::get('/', 'HomeController@index');

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/generateQrcode', 'HomeController@generateQrcode');
Route::post('/downloadQrcode', 'HomeController@downloadQrcode');
Route::get('/contacts/{id}', 'HomeController@contacts');
Route::get('/profiles/{id}', 'HomeController@babies');
Route::post('/users/destroy', 'HomeController@destroy');
route::get('/testsms',function () {



    $notificationBuilder = new PayloadNotificationBuilder('my title');
    $notificationBuilder->setBody('Hello world')
        ->setSound('default');

    $notification = $notificationBuilder->build();

    $topic = new Topics();
    $topic->topic('different');

    $topicResponse = FCM::sendToTopic($topic, null, $notification, null);

    dd($topicResponse);

    $topicResponse->isSuccess();
    $topicResponse->shouldRetry();
    $topicResponse->error();
});


