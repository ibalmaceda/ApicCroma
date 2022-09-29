<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApinotasController;
use App\Http\Controllers\ProcesadorNotasController;

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

Route::get('/', function () {
    return view('apinotas.index');
});

// ruta para acceder a api externa
Route::get('/api/get/notas', [ApinotasController::class, 'getNotas']); 
Route::get('api/get/notas2',[ProcesadorNotasController::class, 'index']);
