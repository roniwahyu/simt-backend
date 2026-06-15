<?php

use Illuminate\Support\Facades\Route;

Route::prefix('persuratan')->name('persuratan.')->group(function () {
    Route::get('/', 'PersuratanController@index')->name('index');
    Route::get('/create', 'PersuratanController@create')->name('create');
    Route::post('/', 'PersuratanController@store')->name('store');
    Route::get('/{id}', 'PersuratanController@show')->name('show');
    Route::get('/{id}/edit', 'PersuratanController@edit')->name('edit');
    Route::put('/{id}', 'PersuratanController@update')->name('update');
    Route::delete('/{id}', 'PersuratanController@destroy')->name('destroy');
});
