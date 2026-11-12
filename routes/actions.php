<?php
use cloudgrayau\oopspam\controllers\LogsController;
use Illuminate\Support\Facades\Route;

// Also mounted on the site action trigger, hence the explicit access checks
Route::middleware(['auth', 'can:accessCp', 'can:accessPlugin-oopspam'])->group(function () {
    Route::post('logs/clear', [LogsController::class, 'clear']);
});