<?php

use Illuminate\Support\Facades\Route;

Route::prefix('perpustakaan')->name('perpustakaan.')->group(function () {
    Route::get('/', 'PerpustakaanController@index')->name('index');
    Route::get('/create', 'PerpustakaanController@create')->name('create');
    Route::post('/', 'PerpustakaanController@store')->name('store');
    Route::get('/{id}', 'PerpustakaanController@show')->name('show');
    Route::get('/{id}/edit', 'PerpustakaanController@edit')->name('edit');
    Route::put('/{id}', 'PerpustakaanController@update')->name('update');
    Route::delete('/{id}', 'PerpustakaanController@destroy')->name('destroy');
});
