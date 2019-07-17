<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('cache/storage/files/{image}/{width?}', '\Koiiiey\Api\Http\Controllers\Api\AdditionalController@resizeImage');


Route::group(['prefix' => 'api/v1', 'namespace' => 'Api', 'middleware' => ['api']], function () {

    Route::get('/models', '\Koiiiey\Api\Http\Controllers\Api\AdditionalController@getModels');
    Route::get('/model/{model}', '\Koiiiey\Api\Http\Controllers\Api\AdditionalController@getModel');
    Route::get('/fields/{model}', '\Koiiiey\Api\Http\Controllers\Api\AdditionalController@getFields');
    Route::get('/types/{model}', '\Koiiiey\Api\Http\Controllers\Api\AdditionalController@getTypes');
    Route::get('/rules/{model}', '\Koiiiey\Api\Http\Controllers\Api\AdditionalController@getRules');

    Route::get('/full/{model}', '\Koiiiey\Api\Http\Controllers\Api\AdditionalController@getFullModel');

    Route::any('/call/{model}/{method}/{params?}', '\Koiiiey\Api\Http\Controllers\Api\EntityController@call');

    Route::post('/auth/register', '\Koiiiey\Api\Http\Controllers\Api\AuthController@postRegister');
    Route::post('/auth/login', '\Koiiiey\Api\Http\Controllers\Api\AuthController@postLogin');
    Route::get('/auth/logout', '\Koiiiey\Api\Http\Controllers\Api\AuthController@getLogout');

    Route::get('/auth/soc/{type}', '\Koiiiey\Api\Http\Controllers\Api\AuthController@goToSoc');
    Route::get('/auth/soc-red/{type}', '\Koiiiey\Api\Http\Controllers\Api\AuthController@social');

    Route::post('/auth/phoneCode', '\Koiiiey\Api\Http\Controllers\Api\AuthController@postPhoneCode');

    Route::post('/auth/email', '\Koiiiey\Api\Http\Controllers\Api\AuthController@postMail');
    Route::post('/auth/password', '\Koiiiey\Api\Http\Controllers\Api\AuthController@changePassword');
    Route::post('/auth/verify', '\Koiiiey\Api\Http\Controllers\Api\AuthController@verify');

    Route::get('/auth/current', '\Koiiiey\Api\Http\Controllers\Api\AuthController@getCurrent');

    Route::group(['prefix' => '{model}'], function () {
        Route::get('/', '\Koiiiey\Api\Http\Controllers\Api\EntityController@index');
        Route::post('/', '\Koiiiey\Api\Http\Controllers\Api\EntityController@create');

        Route::group(['prefix' => '{entity_id}'], function () {
            Route::get('/', '\Koiiiey\Api\Http\Controllers\Api\EntityController@show');
            Route::put('/', '\Koiiiey\Api\Http\Controllers\Api\EntityController@update');
            Route::delete('/', '\Koiiiey\Api\Http\Controllers\Api\EntityController@destroy');
        });

        Route::group(['prefix' => '{parent}/{relation}'], function () {
            Route::get('/', '\Koiiiey\Api\Http\Controllers\Api\RelationController@index');
            Route::post('/', '\Koiiiey\Api\Http\Controllers\Api\RelationController@store');

            Route::group(['prefix' => '{child}'], function () {
                Route::get('/', '\Koiiiey\Api\Http\Controllers\Api\RelationController@show');
                Route::put('/', '\Koiiiey\Api\Http\Controllers\Api\RelationController@update');
                Route::delete('/', '\Koiiiey\Api\Http\Controllers\Api\RelationController@destroy');
            });
        });
    });
});


Route::group(['middleware' => ['web']], function () {
    Route::get('/admin/{a?}', function () {
        return file_get_contents(public_path('/ng-admin/index.html'));
    })->where('a', '.*');
});

Route::group(['middleware' => ['web']], function () {
    Route::get('/{a}', function () {
        return file_get_contents(public_path('/index.html'));
    })->where('a', '.*');
});