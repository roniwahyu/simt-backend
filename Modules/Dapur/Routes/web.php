<?php

use Illuminate\Support\Facades\Route;

Route::prefix('dapur')->name('dapur.')->group(function () {
    Route::get('/', 'DapurController@index')->name('index');
    Route::get('/create', 'DapurController@create')->name('create');
    Route::post('/', 'DapurController@store')->name('store');
    Route::get('/{id}', 'DapurController@show')->name('show');
    Route::get('/{id}/edit', 'DapurController@edit')->name('edit');
    Route::put('/{id}', 'DapurController@update')->name('update');
    Route::delete('/{id}', 'DapurController@destroy')->name('destroy');
});
