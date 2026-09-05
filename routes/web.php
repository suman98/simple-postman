<?php

use App\Http\Controllers\EndpointController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QuickTestController;
use App\Http\Controllers\RequestRunController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/quick-test');

Route::get('/quick-test', QuickTestController::class)->name('quick-test');

Route::post('/api/run', [RequestRunController::class, 'run'])->name('api.run');

Route::resource('projects', ProjectController::class);
Route::put('/projects/{project}/environment', [ProjectController::class, 'updateEnvironment'])->name('projects.environment.update');
Route::resource('projects.endpoints', EndpointController::class)->shallow();
Route::put('/endpoints/{endpoint}/request', [EndpointController::class, 'updateRequest'])->name('endpoints.request.update');
