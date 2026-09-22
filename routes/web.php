<?php

use App\Http\Controllers\ActivityGuideController;
use App\Http\Controllers\AlumniController;
use App\Http\Controllers\AmbalanController;
use App\Http\Controllers\AmbalanMediaController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LearningMaterialController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberPositionController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SkuController;
use App\Http\Controllers\TkkPointController;
use Illuminate\Support\Facades\Route;

Route::get('/', GuestController::class)->name('home');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register')->middleware('throttle:3,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1');

// Password Reset
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request')->middleware('throttle:3,1');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:3,1');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset')->middleware('throttle:3,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:3,1');

Route::middleware(['auth'])->group(function () {
    Route::get('/pending-approval', [AuthController::class, 'pendingApproval'])->name('pending-approval');
    Route::middleware('pembina')->group(function () {
        Route::get('/members/pending', [RegistrationController::class, 'pendingUsers'])->name('members.pending');
        Route::post('/members/pending/{user}/approve', [RegistrationController::class, 'approve'])->name('members.pending.approve');
        Route::post('/members/pending/{user}/reject', [RegistrationController::class, 'reject'])->name('members.pending.reject');
    });
});

Route::middleware(['auth', 'approved'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Public materials (accessible to all authenticated users)
    Route::get('/materials', [LearningMaterialController::class, 'index'])->name('materials.index');
    Route::get('/materials/{material}', [LearningMaterialController::class, 'show'])->name('materials.show');
    Route::get('/materials/{material}/download', [LearningMaterialController::class, 'download'])->name('materials.download');

    // Activity guides (public)
    Route::get('/guides', [ActivityGuideController::class, 'index'])->name('guides.index');
    Route::get('/guides/{guide}', [ActivityGuideController::class, 'show'])->name('guides.show');

    // Mars Ambalan media (public)
    Route::get('/medias', [AmbalanMediaController::class, 'index'])->name('medias.index');
    Route::get('/medias/{media}', [AmbalanMediaController::class, 'show'])->name('medias.show');

    // Announcements (viewable by all authenticated users)
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');

    // Peta Kontur Sulawesi (viewable by all authenticated users)
    Route::get('/peta', [MapController::class, 'index'])->name('peta.index');
    Route::get('/peta/kontur', [MapController::class, 'kontur'])->name('peta.kontur');

    // Attendance (viewable by all authenticated users)
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/{attendanceSession}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::get('/attendance/{attendanceSession}/materi', [AttendanceController::class, 'downloadMateri'])->name('attendance.materi.download');

    // Finance: index viewable by all authenticated users (Anggota lihat iuran sendiri)
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');

    // QR Code scan page for Anggota
    Route::middleware(['role:Anggota'])->group(function () {
        Route::get('/attendance/scan/{qr_token}', [AttendanceController::class, 'scanPage'])->name('attendance.scan');
    });

    Route::middleware(['role:Admin,Pembina,Pengurus'])->group(function () {
        Route::get('/members', [MemberController::class, 'index'])->name('members.index');
        Route::post('/members', [MemberController::class, 'store'])->name('members.store')->middleware('throttle:10,1');
        Route::patch('/members/{member}', [MemberController::class, 'update'])->name('members.update')->middleware('throttle:20,1');
        Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy')->middleware('throttle:10,1');

        Route::post('/members/bulk-change-role', [MemberController::class, 'bulkChangeRole'])->name('members.bulk.role')->middleware('throttle:5,1');

        Route::get('/angkatan', [MemberController::class, 'angkatanIndex'])->name('angkatan.index');
        Route::post('/angkatan', [MemberController::class, 'angkatanStore'])->name('angkatan.store')->middleware('throttle:10,1');
        Route::patch('/angkatan/{angkatan}', [MemberController::class, 'angkatanUpdate'])->name('angkatan.update')->middleware('throttle:10,1');
        Route::delete('/angkatan/{angkatan}', [MemberController::class, 'angkatanDestroy'])->name('angkatan.destroy')->middleware('throttle:5,1');

        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store')->middleware('throttle:20,1');
        Route::patch('/attendance/{attendanceSession}', [AttendanceController::class, 'update'])->name('attendance.update')->middleware('throttle:20,1');
        Route::delete('/attendance/{attendanceSession}', [AttendanceController::class, 'destroy'])->name('attendance.destroy')->middleware('throttle:10,1');
        Route::patch('/attendance-records/{attendance}', [AttendanceController::class, 'updateAttendance'])->name('attendance.records.update')->middleware('throttle:20,1');
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.member.check-in')->middleware('throttle:30,1');
        Route::patch('/finance/{finance}', [FinanceController::class, 'update'])->name('finance.update')->middleware('throttle:20,1');
        Route::delete('/finance/{finance}', [FinanceController::class, 'destroy'])->name('finance.destroy')->middleware('throttle:10,1');
        Route::post('/finance/{finance}/post', [FinanceController::class, 'post'])->name('finance.post')->middleware('throttle:10,1');
        Route::post('/finance/{finance}/reverse', [FinanceController::class, 'reverse'])->name('finance.reverse')->middleware('throttle:10,1');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store')->middleware('throttle:10,1');
        Route::patch('/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update')->middleware('throttle:20,1');
        Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy')->middleware('throttle:10,1');
        Route::post('/inventory-loans', [InventoryController::class, 'loan'])->name('inventory.loans.store')->middleware('throttle:10,1');
        Route::patch('/inventory-loans/{inventoryLoan}/return', [InventoryController::class, 'returnLoan'])->name('inventory.loans.return')->middleware('throttle:10,1');
        Route::post('/inventory-adjustments', [InventoryController::class, 'adjustment'])->name('inventory.adjustments.store')->middleware('throttle:10,1');

        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store')->middleware('throttle:10,1');
        Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update')->middleware('throttle:20,1');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy')->middleware('throttle:10,1');

        // Learning materials management (restricted)
        Route::post('/materials', [LearningMaterialController::class, 'store'])->name('materials.store')->middleware('throttle:10,1');
        Route::patch('/materials/{material}', [LearningMaterialController::class, 'update'])->name('materials.update')->middleware('throttle:20,1');
        Route::delete('/materials/{material}', [LearningMaterialController::class, 'destroy'])->name('materials.destroy')->middleware('throttle:10,1');

        // Activity guides management
        Route::post('/guides', [ActivityGuideController::class, 'store'])->name('guides.store')->middleware('throttle:10,1');
        Route::patch('/guides/{guide}', [ActivityGuideController::class, 'update'])->name('guides.update')->middleware('throttle:20,1');
        Route::delete('/guides/{guide}', [ActivityGuideController::class, 'destroy'])->name('guides.destroy')->middleware('throttle:10,1');

        // Ambalan media management
        Route::post('/medias', [AmbalanMediaController::class, 'store'])->name('medias.store')->middleware('throttle:10,1');
        Route::patch('/medias/{media}', [AmbalanMediaController::class, 'update'])->name('medias.update')->middleware('throttle:20,1');
        Route::delete('/medias/{media}', [AmbalanMediaController::class, 'destroy'])->name('medias.destroy')->middleware('throttle:10,1');

        Route::post('/donations/{donation}/verify', [AlumniController::class, 'verifyDonation'])->name('donations.verify')->middleware('throttle:10,1');

        // Ambalan logo management (Pembina)
        Route::get('/ambalan', [AmbalanController::class, 'edit'])->name('ambalan.edit');
        Route::post('/ambalan/logo', [AmbalanController::class, 'updateLogo'])->name('ambalan.logo.update')->middleware('throttle:5,1');
        Route::delete('/ambalan/logo', [AmbalanController::class, 'destroyLogo'])->name('ambalan.logo.destroy')->middleware('throttle:5,1');
    });

    // Persuratan (Letters)
    Route::middleware(['role:Admin,Pembina,Pengurus'])->group(function () {
        Route::get('/letters', [LetterController::class, 'index'])->name('letters.index');
        Route::post('/letters', [LetterController::class, 'store'])->name('letters.store')->middleware('throttle:10,1');
        Route::patch('/letters/{letter}', [LetterController::class, 'update'])->name('letters.update')->middleware('throttle:20,1');
        Route::delete('/letters/{letter}', [LetterController::class, 'destroy'])->name('letters.destroy')->middleware('throttle:10,1');
        Route::get('/letters/{letter}/print', [LetterController::class, 'print'])->name('letters.print');
        Route::get('/letters/{letter}/download', [LetterController::class, 'download'])->name('letters.download');
        Route::get('/letters/{letter}/generate', [LetterController::class, 'generate'])->name('letters.generate')->middleware('throttle:20,1');
        Route::get('/letters/templates', [LetterController::class, 'templates'])->name('letters.templates');
        Route::post('/letters/templates', [LetterController::class, 'storeTemplate'])->name('letters.templates.store')->middleware('throttle:5,1');
        Route::delete('/letters/templates/{template}', [LetterController::class, 'destroyTemplate'])->name('letters.templates.destroy')->middleware('throttle:5,1');
        Route::post('/letters/preview', [LetterController::class, 'preview'])->name('letters.preview')->middleware('throttle:20,1');
    });

    // Pengurus Positions
    Route::middleware(['role:Admin,Pembina,Pengurus'])->group(function () {
        Route::post('/members/{member}/position', [MemberPositionController::class, 'store'])->name('members.position.store')->middleware('throttle:10,1');
        Route::delete('/members/{member}/position/{position}', [MemberPositionController::class, 'destroy'])->name('members.position.destroy')->middleware('throttle:10,1');
        Route::post('/members/bulk-position', [MemberPositionController::class, 'bulkAssign'])->name('members.position.bulk')->middleware('throttle:5,1');
    });

    Route::middleware(['role:Anggota,Admin,Pembina,Pengurus'])->group(function () {
        Route::get('/sku', [SkuController::class, 'index'])->name('sku.index');
        Route::post('/sku', [SkuController::class, 'store'])->name('sku.store')->middleware('throttle:10,1');
        Route::post('/sku/points', [SkuController::class, 'storePoint'])->name('sku.points.store')->middleware('throttle:10,1');
        Route::patch('/sku/points/{skuPoint}', [SkuController::class, 'update'])->name('sku.points.update')->middleware('throttle:20,1');
        Route::post('/sku/{skuSubmission}/approve', [SkuController::class, 'approve'])->name('sku.approve')->middleware('throttle:20,1');
        Route::post('/sku/{skuSubmission}/reject', [SkuController::class, 'reject'])->name('sku.reject')->middleware('throttle:20,1');
        Route::post('/sku/{skuSubmission}/cancel', [SkuController::class, 'cancel'])->name('sku.cancel')->middleware('throttle:10,1');

        Route::post('/tkk/points', [TkkPointController::class, 'store'])->name('tkk.points.store')->middleware('throttle:10,1');
        Route::patch('/tkk/points/{tkkPoint}', [TkkPointController::class, 'update'])->name('tkk.points.update')->middleware('throttle:20,1');

        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.member.check-in')->middleware('throttle:30,1');
    });

    Route::get('/profile', [MemberController::class, 'profile'])->name('profile.show');
    Route::patch('/profile', [MemberController::class, 'updateProfile'])->name('profile.update')->middleware('throttle:10,1');

    Route::post('/pwa/devices', [PwaController::class, 'registerDevice'])->name('pwa.devices.store')->middleware('throttle:20,1');
});

Route::middleware(['auth', 'role:Alumni'])->prefix('alumni')->name('alumni.')->group(function () {
    Route::get('/dashboard', [AlumniController::class, 'dashboard'])->name('dashboard');
    Route::patch('/profile', [AlumniController::class, 'updateProfile'])->name('profile.update')->middleware('throttle:10,1');
    Route::post('/donations', [AlumniController::class, 'storeDonation'])->name('donations.store')->middleware('throttle:5,1');
    Route::post('/donations/{donation}/cancel', [AlumniController::class, 'cancelDonation'])->name('donations.cancel')->middleware('throttle:5,1');
});
