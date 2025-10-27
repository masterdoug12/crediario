<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ClienteMovimentoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::post('/clientes', [ClienteController::class, 'store']);
    Route::get('/clientes/{cliente}', [ClienteController::class, 'show']);
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update']);
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy']);

    Route::get('/clientes/{cliente}/movimentos', [ClienteMovimentoController::class, 'index']);
    Route::post('/clientes/{cliente}/debito', [ClienteMovimentoController::class, 'storeDebito']);
    Route::post('/clientes/{cliente}/pagamento', [ClienteMovimentoController::class, 'storePagamento']);
});
