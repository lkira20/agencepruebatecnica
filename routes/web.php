<?php

use App\Http\Controllers\DesempennoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [DesempennoController::class, 'consultor'])->name('consultor');
Route::post('/consultar', [DesempennoController::class, 'postConsultar'])->name('consultar');
Route::get('/cliente', [DesempennoController::class, 'cliente'])->name('cliente');
Route::post('/informe', [DesempennoController::class, 'informe'])->name('informe');
Route::post('/grafico', [DesempennoController::class, 'grafico'])->name('grafico');
Route::post('/pizza', [DesempennoController::class, 'pizza'])->name('pizza');

