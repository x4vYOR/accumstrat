<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Auth::routes([
    'register' => false, // Registration Routes...
    'reset' => false, // Password Reset Routes...
    'verify' => false, // Email Verification Routes...
  ]);

Route::post('/webhook', 'PairController@webhook')->name('nuevo.par');
  
Route::group(['middleware' => 'auth'], function () {
    Route::get('/resultados', 'HomeController@index')->name('resultados');

    Route::get('/pares', 'PairController@index')->name('pares');
    Route::post('/pares/nuevo', 'PairController@savePair')->name('nuevo.par');
    Route::get('/pares/cargar/tickers/{id}', 'PairController@loadData');

    Route::get('/configuracion', 'SettingsController@index')->name('configuracion');
    Route::get('/', 'HomeController@index')->name('home');
    Route::patch('/par/{id}/estado', 'PairController@onPair');
});

Auth::routes();

