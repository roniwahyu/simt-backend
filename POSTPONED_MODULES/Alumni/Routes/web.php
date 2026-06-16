<?php

use Illuminate\Support\Facades\Route;

Route::prefix('alumni')->name('alumni.')->group(function () {
    Route::get('/', 'AlumniController@index')->name('index');
    Route::get('/create', 'AlumniController@create')->name('create');
    Route::post('/', 'AlumniController@store')->name('store');
    Route::get('/{id}', 'AlumniController@show')->name('show');
    Route::get('/{id}/edit', 'AlumniController@edit')->name('edit');
    Route::put('/{id}', 'AlumniController@update')->name('update');
    Route::delete('/{id}', 'AlumniController@destroy')->name('destroy');
});
