<?php

use Illuminate\Support\Facades\Route;

Route::prefix('kantin')->name('kantin.')->group(function () {
    Route::get('/', 'KantinController@index')->name('index');
    Route::get('/create', 'KantinController@create')->name('create');
    Route::post('/', 'KantinController@store')->name('store');
    Route::get('/{id}', 'KantinController@show')->name('show');
    Route::get('/{id}/edit', 'KantinController@edit')->name('edit');
    Route::put('/{id}', 'KantinController@update')->name('update');
    Route::delete('/{id}', 'KantinController@destroy')->name('destroy');
});
