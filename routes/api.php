<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;

Route::post('register',[AuthController::class,'register'])->middleware('throttle:10,1');
Route::post('login',[AuthController::class,'login'])->middleware('throttle:10,1');

Route::group([
    'middleware' => ['jwt.auth', 'throttle:60,1'] // 60 requests per minute per user/ip
], function() {
    Route::get('me',[AuthController::class,'me']);
    Route::get('wallet/balance',[WalletController::class,'balance']);
    Route::post('wallet/add',[WalletController::class,'addFunds']);
    Route::post('wallet/transfer',[WalletController::class,'transfer']);
    Route::post('wallet/withdraw',[WalletController::class,'withdraw']);
    Route::get('wallet/transactions',[WalletController::class,'transactions']);
});

Route::get('test', function() {
    return response()->json(['message'=>'API works!']);
});
