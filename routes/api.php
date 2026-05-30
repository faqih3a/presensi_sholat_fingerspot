<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::post('/fingerspot-webhook', [WebhookController::class, 'handle']);
