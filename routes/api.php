<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaloriesController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\RecipesController;
use App\Http\Controllers\ScrappedDayController;
use App\Http\Controllers\WeightController;
use App\Models\Config;
use Carbon\Carbon;
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

Route::middleware('auth:sanctum')->get('/weight', function (Request $request) {
    $config = Config::forUser($request->user()->id);

    $timezone = 'America/Guayaquil';
    $date = $request->filled('date')
        ? Carbon::parse($request->input('date'), $timezone)
        : Carbon::now($timezone);

    return [
        'status' => 'success',
        'weight' => round($config->weightAsOf($date)->weight * 2.204623),
        'target' => $config->target,
    ];
});

Route::middleware("auth:sanctum")->get('/calories/remaining', [CaloriesController::class, 'remaining']);

Route::middleware("auth:sanctum")->get('/weights', [WeightController::class, 'index']);

Route::controller(ConfigController::class)
    ->prefix('/profile')
    ->middleware("auth:sanctum")
    ->group(function () {
        Route::get('', 'show');
        Route::put('', 'update');
    })
;

Route::controller(ScrappedDayController::class)
    ->prefix('/scrapped-days')
    ->middleware("auth:sanctum")
    ->group(function () {
        Route::post('', 'store');
        Route::delete('{date}', 'destroy');
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
