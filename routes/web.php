<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\LandingPageController;
use App\Http\Controllers\Customer\CatalogController;
use App\Http\Controllers\Customer\DietaryProfileController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\PaymentProofController;
use App\Http\Controllers\Customer\OrderHistoryController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\WeeklyManagementController;
use App\Http\Controllers\Admin\OrderVerificationController;
use App\Http\Controllers\Admin\KitchenReportController;
use App\Http\Controllers\Auth\AuthController;
use Inertia\Inertia;
use App\Models\Allergen;

// Public Landing Page
Route::get('/', LandingPageController::class)->name('home');

// Auth Routes
Route::get('/login', function () { return Inertia::render('Auth/Login'); })->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/register', function () { return Inertia::render('Auth/Register'); })->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Customer Routes (Protected)
Route::middleware(['auth'])->group(function () {
    Route::get('/dietary-setup', function () {
        return Inertia::render('Customer/Onboarding/DietarySetup', [
            'allergens' => Allergen::all(),
            'userAllergens' => request()->user() ? request()->user()->allergens : []
        ]);
    })->name('dietary-setup');

    Route::post('/dietary-profile', [DietaryProfileController::class, 'update'])->name('dietary-profile.update');

    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
    Route::post('/birthday-reward/claim', [\App\Http\Controllers\Customer\BirthdayRewardController::class, 'claim'])->name('birthday-reward.claim');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/history', [OrderHistoryController::class, 'index'])->name('history.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/orders/{order}', function (\App\Models\Order $order) {
        return Inertia::render('Customer/Checkout/Payment', ['order' => $order]);
    })->name('orders.show');

    Route::post('/orders/{order}/payment-proof', [PaymentProofController::class, 'store'])->name('payment-proof.store');
});

  // Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'is_admin'])->group(function () {
    // Redirect /admin to /admin/menus
    Route::redirect('/', '/admin/menus')->name('dashboard');

    // Menus CRUD
    Route::get('/menus', [MenuController::class, 'index'])->name('menus.index');
    Route::get('/menus/create', [MenuController::class, 'create'])->name('menus.create');
    Route::post('/menus', [MenuController::class, 'store'])->name('menus.store');
    Route::get('/menus/{menu}/edit', [MenuController::class, 'edit'])->name('menus.edit');
    Route::put('/menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
    Route::delete('/menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');

    // Weekly Management
    Route::get('/weekly-management', [WeeklyManagementController::class, 'index'])->name('weekly-management.index');
    Route::post('/weekly-management', [WeeklyManagementController::class, 'update'])->name('weekly-management.update');

    // Users Management
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');

    // Orders Verification
    Route::get('/orders', [OrderVerificationController::class, 'index'])->name('orders.index');
    Route::put('/orders/{order}/verify', [OrderVerificationController::class, 'update'])->name('orders.verify');

    // Kitchen Report
    Route::get('/kitchen-report', [KitchenReportController::class, 'index'])->name('kitchen-report.index');

    // Allergens / Bahan Baku API
    Route::get('/allergens', [\App\Http\Controllers\Admin\AllergenController::class, 'index'])->name('allergens.index');
    Route::post('/allergens', [\App\Http\Controllers\Admin\AllergenController::class, 'store'])->name('allergens.store');
});
