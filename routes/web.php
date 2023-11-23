<?php

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
Route::get('/login', [\App\Http\Controllers\BillingController::class, 'loginPage'])->name('login');
Route::post('/login', [\App\Http\Controllers\BillingController::class, 'login']);
Route::get('/register', [\App\Http\Controllers\BillingController::class, 'registerPage']);
Route::post('/register', [\App\Http\Controllers\BillingController::class, 'register']);
Route::get('/logout', [\App\Http\Controllers\BillingController::class, 'logout']);

Route::middleware('auth:web')->group(function (){
    Route::get('/', [\App\Http\Controllers\BillingController::class, 'mainPage']);
    Route::post('/workspace/create', [\App\Http\Controllers\BillingController::class, 'createWorkspace']);
    Route::get('/workspace/{workspace:id}', [\App\Http\Controllers\BillingController::class, 'workSpacePage']);
    Route::post('/workspace/{workspace:id}/create/token', [\App\Http\Controllers\BillingController::class, 'createToken']);
    Route::post('/token/{token:id}/deactivate', [\App\Http\Controllers\BillingController::class, 'deactivateToken']);
    Route::post('/token/{token:id}/activate', [\App\Http\Controllers\BillingController::class, 'activateToken']);
    Route::post('/quota/{workspace:id}/create', [\App\Http\Controllers\BillingController::class, 'createQuota']);
    Route::post('/quota/{workspace:id}/delete', [\App\Http\Controllers\BillingController::class, 'deleteQuota']);
});
//Route::get('/', function () {
//    return view('welcome');
//});
