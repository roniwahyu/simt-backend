<?php

use Illuminate\Support\Facades\Route;

Route::prefix('presensi')->name('presensi.')->group(function () {
    Route::get('/', 'PresensiController@index')->name('index');
    Route::get('/create', 'PresensiController@create')->name('create');
    Route::post('/', 'PresensiController@store')->name('store');
    Route::get('/{id}', 'PresensiController@show')->name('show');
    Route::get('/{id}/edit', 'PresensiController@edit')->name('edit');
    Route::put('/{id}', 'PresensiController@update')->name('update');
    Route::delete('/{id}', 'PresensiController@destroy')->name('destroy');
});
