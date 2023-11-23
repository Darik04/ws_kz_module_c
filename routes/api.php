<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WrapperController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('/chat/conversation', [WrapperController::class, 'createConversation']);
Route::put('/chat/conversation/{conversation:conversation_id}', [WrapperController::class, 'continueConversation']);
Route::get('/chat/conversation/{conversation:conversation_id}', [WrapperController::class, 'getPartialConversation']);


Route::post('/imagegeneration/generate', [WrapperController::class, 'generateImageBasedPrompt']);
Route::get('/imagegeneration/status/{job:job_id}', [WrapperController::class, 'getStatusJob']);
Route::get('/imagegeneration/result/{job:job_id}', [WrapperController::class, 'getResultJob']);
Route::post('/imagegeneration/upscale', [WrapperController::class, 'upscaleImage']);
Route::post('/imagegeneration/zoom/in', [WrapperController::class, 'zoomIn']);
Route::post('/imagegeneration/zoom/out', [WrapperController::class, 'zoomOut']);
Route::post('/imagerecognition/recognize', [WrapperController::class, 'imageRecognition']);


Route::prefix('/v1')->group(function (){
   Route::post('import', [\App\Http\Controllers\BillingController::class, 'importCSV']);
});
//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});
