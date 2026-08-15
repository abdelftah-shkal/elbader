<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('categories')
    ->name('categories.')
    ->controller(CategoryController::class)
    ->group(function () {
        Route::delete('bulk-delete', 'bulkDestroy')->name('bulkDestroy');
        Route::get('parents/{category?}', 'parents')->name('parents');
        Route::get('tree', 'tree')->name('tree');
        Route::resource('', CategoryController::class)
            ->parameters(['' => 'category'])
            ->except(['show']);
        Route::get('{category}', 'show')->name('show');
    });
