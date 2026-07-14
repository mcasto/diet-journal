<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaloriesController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\RecipesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)
    ->group(function () {
        Route::get('/user', function (Request $request) {
            return ['status' => 'success', 'user' => $request->user()];
        })->middleware('auth:sanctum');

        Route::post('/login', 'login');
    });

Route::controller(FoodController::class)
    ->prefix('/food')
    ->middleware("auth:sanctum")
    ->group(function () {
        Route::get('', 'index');
        Route::post('', 'store');
        Route::get('latest/{end}', 'latest');
        Route::get('search', 'search');
        Route::get('{id}', 'show');
        Route::put('{id}', 'update');
        Route::delete('{id}', 'destroy');
    })
;

Route::controller(CaloriesController::class)
    ->prefix('/calories')
    ->middleware("auth:sanctum")
    ->group(function () {
        Route::put('', 'update');
    })
;

Route::controller(RecipesController::class)
    ->prefix('/recipes')
    ->middleware("auth:sanctum")
    ->group(function () {
        Route::get('{consumed}', 'show');
        Route::put('', 'update');
    })
;
