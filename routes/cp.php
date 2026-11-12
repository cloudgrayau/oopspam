<?php
use cloudgrayau\oopspam\controllers\{LandingController, LogsController, ReputationController, TestController};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:accessCp', 'can:accessPlugin-oopspam'])->prefix('oopspam')->group(function () {
  Route::get('', LandingController::class);
  Route::get('logs', [LogsController::class, 'index']);
  Route::delete('logs', [LogsController::class, 'destroy']);
  Route::get('logs/{id}', [LogsController::class, 'show'])->whereNumber('id');
  Route::post('logs/{id}/report', [LogsController::class, 'report'])->whereNumber('id');
  Route::get('reputation', [ReputationController::class, 'show']);
  Route::post('reputation', [ReputationController::class, 'run']);
  Route::get('test', [TestController::class, 'show']);
  Route::post('test', [TestController::class, 'run']);
  Route::post('test/refresh', [TestController::class, 'refresh']);
});