<?php

use Illuminate\Support\Facades\Route;

Route::prefix('pegawai')->name('pegawai.')->group(function () {
    Route::get('/', 'PegawaiController@index')->name('index');
    Route::get('/create', 'PegawaiController@create')->name('create');
    Route::post('/', 'PegawaiController@store')->name('store');
    Route::get('/{id}', 'PegawaiController@show')->name('show');
    Route::get('/{id}/edit', 'PegawaiController@edit')->name('edit');
    Route::put('/{id}', 'PegawaiController@update')->name('update');
    Route::delete('/{id}', 'PegawaiController@destroy')->name('destroy');
});
