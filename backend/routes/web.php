<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;

Route::get('/', function () {
    return redirect()->route('categories.index');
});

Route::delete('/categories/bulk-delete', [CategoryController::class, 'bulkDestroy'])
    ->name('categories.bulkDestroy');

Route::get('/categories/parents/{category?}', [CategoryController::class, 'parents'])
    ->name('categories.parents');

Route::get('/categories/tree', [CategoryController::class, 'tree'])
    ->name('categories.tree');

Route::get('/categories/{category}', [CategoryController::class, 'show'])
    ->name('categories.show');

Route::resource('categories', CategoryController::class)
    ->except(['show']);
