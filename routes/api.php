<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\controllers\Api\ProductPriceController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::apiResource('products', ProductController::class);


Route::get('products/{product}/prices', [
    ProductPriceController::class, 'index'
]);

Route::post('products/{product}/prices', [
    ProductPriceController::class, 'store'
]);