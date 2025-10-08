<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChargingStationController;
use App\Http\Controllers\Admin\StationController; // สำหรับสถานี
use App\Http\Controllers\Admin\UserController as AdminUserController; // ✅ เพิ่มบรรทัดนี้
use App\Http\Controllers\Admin\ReportController;

// -------------------------------
// Admin (ต้องเป็นแอดมินเท่านั้น)
// -------------------------------
Route::middleware(['auth', 'is_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::resource('stations', StationController::class);

        // Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::post('/reports/{report}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve');
        Route::post('/reports/{report}/reject',  [ReportController::class, 'reject'])->name('reports.reject');
        Route::delete('/reports/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');
 
        //  Users (index/edit/update/destroy/restore)
        Route::get('users',               [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/{user}/edit',   [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}',        [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}',     [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{id}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
    });

// -------------------------------
// User Dashboard + แผนที่รวม
// -------------------------------
Route::middleware(['auth'])->group(function () {
    Route::get('/user/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');
});
Route::get('/stations/map', [ChargingStationController::class, 'map'])->name('stations.map');

// API สำหรับหน้าแผนที่
Route::get('/api/stations', [ChargingStationController::class, 'apiStations'])->name('api.stations');

// Smart redirect
Route::get('/dashboard', function () {
    $user = auth()->user();
    return ($user && $user->role_id == 2)
        ? redirect()->route('admin.dashboard')
        : redirect()->route('user.dashboard');
})->middleware(['auth'])->name('dashboard');

// หน้าแรก
Route::get('/', fn() => view('welcome'));

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// สถานี (public)
Route::get('/stations', [ChargingStationController::class, 'index'])->name('stations.index');
Route::get('/stations/{id}', [ChargingStationController::class, 'show'])->whereNumber('id')->name('stations.show');
Route::delete('stations/{station}/image', [\App\Http\Controllers\Admin\StationController::class, 'destroyImage'])
    ->name('admin.stations.image.destroy');
Route::get('/stations/{id}/navigate', [\App\Http\Controllers\ChargingStationController::class, 'navigate'])
    ->whereNumber('id')->name('stations.navigate');

// User ส่งคำขอเพิ่มสถานี
Route::middleware(['auth'])->group(function () {
    Route::get('/user/request-station', [\App\Http\Controllers\UserRequestController::class, 'create'])
        ->name('user.request.create');
    Route::post('/user/request-station', [\App\Http\Controllers\UserRequestController::class, 'store'])
        ->name('user.request.store');
});

// Admin: รายการรอตรวจสอบ + อนุมัติ/ปฏิเสธ
Route::middleware(['auth','is_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/stations/pending', [\App\Http\Controllers\Admin\StationController::class, 'pending'])
        ->name('stations.pending');
    Route::post('/stations/{id}/approve', [\App\Http\Controllers\Admin\StationController::class, 'approve'])
        ->name('stations.approve');
    Route::delete('/stations/{id}/reject', [\App\Http\Controllers\Admin\StationController::class, 'reject'])
        ->name('stations.reject');
});


require __DIR__.'/auth.php';
