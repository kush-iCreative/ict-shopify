<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopDataController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Shop data endpoints
Route::get('/shops', [ShopDataController::class, 'getAllShops']);
Route::get('/shops/{shopDomain}', [ShopDataController::class, 'getShopData']);
Route::get('/shops/{shopDomain}/history', [ShopDataController::class, 'getInstallationHistory']);
Route::post('/shops/{shopDomain}/metadata', [ShopDataController::class, 'updateShopMetadata']);






