<?php

use Illuminate\Support\Facades\Route;

Route::prefix('akademik')->name('akademik.')->group(function () {
    Route::get('/', 'AkademikController@index')->name('index');
    Route::get('/create', 'AkademikController@create')->name('create');
    Route::post('/', 'AkademikController@store')->name('store');
    Route::get('/{id}', 'AkademikController@show')->name('show');
    Route::get('/{id}/edit', 'AkademikController@edit')->name('edit');
    Route::put('/{id}', 'AkademikController@update')->name('update');
    Route::delete('/{id}', 'AkademikController@destroy')->name('destroy');
});
