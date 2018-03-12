<?php

Route::group(['prefix' => 'api/v1', 'namespace' => 'Api', 'middleware' => ['api', 'web']], function () {

    Route::get('/model/{model}', '\Koiiiey\Api\Http\Controllers\Api\AdditionalController@getModel');
    Route::get('/types/{model}', '\Koiiiey\Api\Http\Controllers\Api\AdditionalController@getTypes');
    Route::get('/rules/{model}', '\Koiiiey\Api\Http\Controllers\Api\AdditionalController@getRules');

    Route::post('/auth/register', '\Koiiiey\Api\Http\Controllers\Api\AuthController@postRegister');
    Route::post('/auth/login', '\Koiiiey\Api\Http\Controllers\Api\AuthController@postLogin');

    Route::post('/auth/email', '\Koiiiey\Api\Http\Controllers\Api\AuthController@postMail');
    Route::post('/auth/password', '\Koiiiey\Api\Http\Controllers\Api\AuthController@changePassword');
    Route::post('/auth/verify', '\Koiiiey\Api\Http\Controllers\Api\AuthController@verify');

    Route::get('/auth/checktoken', '\Koiiiey\Api\Http\Controllers\Api\AuthController@checkToken');

    Route::get('/auth/current', '\Koiiiey\Api\Http\Controllers\Api\AuthController@getCurrent');

    Route::group(['prefix' => '{model}'], function () {
        Route::get('/', '\Koiiiey\Api\Http\Controllers\Api\EntityController@index');
        Route::post('/', '\Koiiiey\Api\Http\Controllers\Api\EntityController@store');

        Route::group(['prefix' => '{entity}'], function () {
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