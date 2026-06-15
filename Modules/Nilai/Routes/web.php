<?php

use Illuminate\Support\Facades\Route;

Route::prefix('nilai')->name('nilai.')->group(function () {
    Route::get('/', 'NilaiController@index')->name('index');
    Route::get('/create', 'NilaiController@create')->name('create');
    Route::post('/', 'NilaiController@store')->name('store');
    Route::get('/{id}', 'NilaiController@show')->name('show');
    Route::get('/{id}/edit', 'NilaiController@edit')->name('edit');
    Route::put('/{id}', 'NilaiController@update')->name('update');
    Route::delete('/{id}', 'NilaiController@destroy')->name('destroy');
});
