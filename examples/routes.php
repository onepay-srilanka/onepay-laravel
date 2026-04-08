<?php

/**
 * Example route registration.
 * Add these to your application's routes/api.php or routes/web.php.
 */

use App\Http\Controllers\OnePayController;
use Illuminate\Support\Facades\Route;

Route::post('/checkout', [OnePayController::class, 'checkout'])
    ->middleware('auth')
    ->name('onepay.checkout');
