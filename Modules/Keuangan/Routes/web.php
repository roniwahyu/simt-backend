<?php

use Illuminate\Support\Facades\Route;

Route::prefix('keuangan')->name('keuangan.')->group(function () {
    Route::get('/', 'KeuanganController@index')->name('index');
    Route::get('/create', 'KeuanganController@create')->name('create');
    Route::post('/', 'KeuanganController@store')->name('store');
    Route::get('/{id}', 'KeuanganController@show')->name('show');
    Route::get('/{id}/edit', 'KeuanganController@edit')->name('edit');
    Route::put('/{id}', 'KeuanganController@update')->name('update');
    Route::delete('/{id}', 'KeuanganController@destroy')->name('destroy');
});
