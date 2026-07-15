<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaloriesController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\RecipesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

Route::get('/weight', function () {
    $config = json_decode(Storage::disk('local')->get('config.json'));

    return [
        'status' => 'success',
        'weight' => round($config->weight * 2.204623),
        'target' => $config->target,
    ];
});

Route::post('/weight', function (Request $request) {
    $config = json_decode(Storage::disk('local')->get('config.json'));

    $validator = Validator::make($request->all(), [
        'weight' => 'required|numeric|min:0',
        'target' => ['nullable', 'string', Rule::in(array_keys(get_object_vars($config->targets)))],
    ]);

    if ($validator->fails()) {
        return ['status' => 401, 'message' => 'Malformed request.'];
    }

    $valid = $validator->valid();

    $config->weight = round($valid['weight'] / 2.204623, 2);

    if (isset($valid['target'])) {
        $config->target = $valid['target'];
    }

    Storage::disk('local')->put('config.json', json_encode($config));

    return ['status' => 'success'];
});

Route::get('/bmr', [CaloriesController::class, 'bmr']);
Route::get('/calories/remaining', [CaloriesController::class, 'remaining']);

Route::controller(RecipesController::class)
    ->prefix('/recipes')
    ->middleware("auth:sanctum")
    ->group(function () {
        Route::get('{consumed}', 'show');
        Route::put('', 'update');
    })
;
