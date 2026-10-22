<?php

use App\Http\Controllers\Api\BotOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for WhatsApp Bot & External Services
|--------------------------------------------------------------------------
*/

Route::prefix('bot')->group(function () {
    // 1. Ambil menu mingguan (Senin - Jumat)
    Route::get('/menus/weekly', [BotOrderController::class, 'getWeeklyMenu']);

    // 2. Cek user & profil alerginya via nomor WhatsApp
    Route::post('/customer/check', [BotOrderController::class, 'checkCustomer']);

    // 3. Validasi alergi atas menu-menu yang dipilih
    Route::post('/orders/validate-allergens', [BotOrderController::class, 'validateAllergens']);

    // 4. Buat pesanan baru dari bot
    Route::post('/orders', [BotOrderController::class, 'createOrder']);

    // 5. Upload bukti pembayaran dari bot
    Route::post('/orders/{order}/payment-proof', [BotOrderController::class, 'uploadPaymentProof']);
});
