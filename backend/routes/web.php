<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
Route::get('/', function () {
    return view('welcome');
});
Route::resource('categories', CategoryController::class)
->except([
    'show',
]);   

Route::delete('/categories/bulk-delete', [CategoryController::class, 'bulkDestroy'])
    ->name('categories.bulkDestroy');

Route::get('/categories/parents', [CategoryController::class, 'parents'])
    ->name('categories.parents');

Route::get('/categories/tree', [CategoryController::class, 'tree'])
    ->name('categories.tree');

Route::get('/categories/{category}', [CategoryController::class, 'show'])
    ->name('categories.show');
