<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;



Route::group(['prefix' => 'tasks', 'as' => 'tasks.', 'middleware' => 'auth:sanctum'], function () {
    Route::get('/', [TaskController::class, 'index'])->name('index');
    Route::post('/', [TaskController::class, 'create'])->name('create');
    Route::get('/{id}', [TaskController::class, 'show'])->name('show');
    Route::put('/{id}', [TaskController::class, 'update'])->name('update');
    Route::patch('/{id}', [TaskController::class, 'update'])->name('update');
    Route::delete('/{id}', [TaskController::class, 'delete'])->name('delete');
});


