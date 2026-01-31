<?php

use App\Http\Controllers\HomePageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/categories', [HomePageController::class, 'getCategories']);
Route::get('/high-demand', [HomePageController::class, 'highDemand']);
Route::get('/featured', [HomePageController::class, 'featured']);
Route::get('/price-changes', [HomePageController::class, 'priceChanges']);
Route::get('/products', [HomePageController::class, 'products']);
Route::get('/locations', [HomePageController::class, 'locations']);
