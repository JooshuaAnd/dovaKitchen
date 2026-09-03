<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';
$app = app();
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$request = Illuminate\Http\Request::create('/admin/weekly-management', 'POST', ['date' => '2026-09-03', 'menu_ids' => [1]]);
$controller = app()->make(App\Http\Controllers\Admin\WeeklyManagementController::class);
try {
    $response = $controller->update($request);
    echo "Success: " . get_class($response) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
