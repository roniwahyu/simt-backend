<?php

use Illuminate\Support\Facades\Route;

Route::prefix('ppdb')->name('ppdb.')->group(function () {
    Route::get('/', 'PpdbController@index')->name('index');
    Route::get('/create', 'PpdbController@create')->name('create');
    Route::post('/', 'PpdbController@store')->name('store');
    Route::get('/{id}', 'PpdbController@show')->name('show');
    Route::get('/{id}/edit', 'PpdbController@edit')->name('edit');
    Route::put('/{id}', 'PpdbController@update')->name('update');
    Route::delete('/{id}', 'PpdbController@destroy')->name('destroy');
});
