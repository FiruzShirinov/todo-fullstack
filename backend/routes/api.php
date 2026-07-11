<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

// Interactive API docs (Redoc UI) rendered from the canonical OpenAPI spec.
Route::view('/docs', 'docs')->name('docs');

// The OpenAPI spec itself. Single source of truth lives at repo-root docs/;
// in Docker it's provided via a read-only volume mount (see docker-compose.yml).
Route::get('/openapi.yaml', function () {
    $path = base_path('../docs/openapi.yaml');
    abort_unless(is_file($path), 404, 'OpenAPI spec not found.');

    return response(file_get_contents($path), 200, [
        'Content-Type' => 'application/yaml; charset=utf-8',
    ]);
})->name('docs.spec');

Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/user', [AuthController::class, 'me'])->name('auth.me');

    Route::apiResource('tasks', TaskController::class);
});
