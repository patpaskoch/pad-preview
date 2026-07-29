<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pad-app');
Route::view('/privacy', 'privacy');

Route::post('/log', [LogController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('log.store');

Route::post('/feedback', [FeedbackController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('feedback.store');

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::middleware('admin.auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/admin/screenshot/{path}', [AdminController::class, 'screenshot'])
        ->where('path', '.*')
        ->name('admin.screenshot');
    Route::delete('/admin/visitors/{session}', [AdminController::class, 'destroyVisitor'])->name('admin.visitors.destroy');
    Route::delete('/admin/feedback/{id}', [AdminController::class, 'destroyFeedback'])->name('admin.feedback.destroy');
});
