<?php

use Illuminate\Support\Facades\Route;

Route::prefix('pendaftaran')->name('pendaftaran.')->group(function () {
    Route::get('/', 'PendaftaranController@index')->name('index');
    Route::get('/create', 'PendaftaranController@create')->name('create');
    Route::post('/', 'PendaftaranController@store')->name('store');
    Route::get('/{id}', 'PendaftaranController@show')->name('show');
    Route::get('/{id}/edit', 'PendaftaranController@edit')->name('edit');
    Route::put('/{id}', 'PendaftaranController@update')->name('update');
    Route::delete('/{id}', 'PendaftaranController@destroy')->name('destroy');
});
