<?php

use Illuminate\Support\Facades\Route;

Route::prefix('berita')->name('berita.')->group(function () {
    Route::get('/', 'BeritaController@index')->name('index');
    Route::get('/create', 'BeritaController@create')->name('create');
    Route::post('/', 'BeritaController@store')->name('store');
    Route::get('/{id}', 'BeritaController@show')->name('show');
    Route::get('/{id}/edit', 'BeritaController@edit')->name('edit');
    Route::put('/{id}', 'BeritaController@update')->name('update');
    Route::delete('/{id}', 'BeritaController@destroy')->name('destroy');
});
