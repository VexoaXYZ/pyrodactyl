<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Api\Client\AuthTokenController;

/*
|--------------------------------------------------------------------------
| API Auth Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/auth
|
| These routes are public (no bearer token required) and allow
| exchanging credentials for an API token.
|
*/

Route::post('/token', [AuthTokenController::class, 'create']);
