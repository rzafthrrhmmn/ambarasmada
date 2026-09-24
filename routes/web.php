<?php

use App\Http\Controllers\ActivityGuideController;
use App\Http\Controllers\AlumniController;
use App\Http\Controllers\AmbalanController;
use App\Http\Controllers\AmbalanMediaController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FieldGuideController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\HealthSafetyController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LearningMaterialController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberPositionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SkuController;
use App\Http\Controllers\SystemPointController;
use App\Http\Controllers\SystemToolController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TkkPointController;
use App\Http\Controllers\UserPermissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', GuestController::class)->name('home');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
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
    });

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

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::post('/notifications/broadcast', [NotificationController::class, 'createNotificationForUsers'])->name('notifications.broadcast')->middleware('throttle:5,1');

    // Events/Kegiatan
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::post('/events', [EventController::class, 'store'])->name('events.store')->middleware('throttle:10,1');
    Route::patch('/events/{event}', [EventController::class, 'update'])->name('events.update')->middleware('throttle:20,1');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy')->middleware('throttle:10,1');
    Route::post('/events/{event}/join', [EventController::class, 'join'])->name('events.join')->middleware('throttle:10,1');
    Route::patch('/events/{event}/participant', [EventController::class, 'updateParticipant'])->name('events.participant.update')->middleware('throttle:20,1');

    // Meetings/Permusyawaratan
    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
    Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store')->middleware('throttle:10,1');
    Route::patch('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update')->middleware('throttle:20,1');
    Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy')->middleware('throttle:10,1');
    Route::post('/meetings/{meeting}/agenda', [MeetingController::class, 'addAgenda'])->name('meetings.agenda.store')->middleware('throttle:10,1');
    Route::post('/meetings/{meeting}/minute', [MeetingController::class, 'storeMinute'])->name('meetings.minute.store')->middleware('throttle:10,1');
    Route::post('/meetings/{meeting}/vote', [MeetingController::class, 'vote'])->name('meetings.vote')->middleware('throttle:10,1');
    Route::post('/meetings/{meeting}/attendee', [MeetingController::class, 'toggleAttendee'])->name('meetings.attendee.toggle')->middleware('throttle:10,1');

    // Assessments/Penilaian
    Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
    Route::post('/assessments', [AssessmentController::class, 'store'])->name('assessments.store')->middleware('throttle:10,1');
    Route::patch('/assessments/{assessment}', [AssessmentController::class, 'update'])->name('assessments.update')->middleware('throttle:20,1');
    Route::delete('/assessments/{assessment}', [AssessmentController::class, 'destroy'])->name('assessments.destroy')->middleware('throttle:10,1');
    Route::post('/assessments/{assessment}/detail', [AssessmentController::class, 'storeDetail'])->name('assessments.detail.store')->middleware('throttle:10,1');

    // Certificates/Sertifikat
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::post('/certificates', [CertificateController::class, 'store'])->name('certificates.store')->middleware('throttle:10,1');
    Route::patch('/certificates/{certificate}', [CertificateController::class, 'update'])->name('certificates.update')->middleware('throttle:20,1');
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');

    // Field Guides/Buku Saku
    Route::get('/field-guides', [FieldGuideController::class, 'index'])->name('field-guides.index');
    Route::post('/field-guides', [FieldGuideController::class, 'store'])->name('field-guides.store')->middleware('throttle:10,1');
    Route::patch('/field-guides/{guide}', [FieldGuideController::class, 'update'])->name('field-guides.update')->middleware('throttle:20,1');
    Route::delete('/field-guides/{guide}', [FieldGuideController::class, 'destroy'])->name('field-guides.destroy')->middleware('throttle:10,1');

    // Articles/Blog
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store')->middleware('throttle:10,1');
    Route::patch('/articles/{article}', [ArticleController::class, 'update'])->name('articles.update')->middleware('throttle:20,1');
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy')->middleware('throttle:10,1');

    // Gallery
    Route::get('/galleries', [GalleryController::class, 'index'])->name('galleries.index');
    Route::post('/galleries', [GalleryController::class, 'store'])->name('galleries.store')->middleware('throttle:10,1');
    Route::delete('/galleries/{gallery}', [GalleryController::class, 'destroy'])->name('galleries.destroy')->middleware('throttle:10,1');

    // Teams/Gugus Depan
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store')->middleware('throttle:10,1');
    Route::patch('/teams/{team}', [TeamController::class, 'update'])->name('teams.update')->middleware('throttle:20,1');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy')->middleware('throttle:10,1');
    Route::post('/teams/{team}/member', [TeamController::class, 'addMember'])->name('teams.member.store')->middleware('throttle:10,1');
    Route::delete('/teams/{team}/member/{teamMember}', [TeamController::class, 'removeMember'])->name('teams.member.destroy')->middleware('throttle:10,1');
    Route::post('/teams/{team}/task', [TeamController::class, 'createTask'])->name('teams.task.store')->middleware('throttle:10,1');
    Route::patch('/teams/task/{task}', [TeamController::class, 'updateTask'])->name('teams.task.update')->middleware('throttle:20,1');

    // Reminders
    Route::get('/reminders', [ReminderController::class, 'index'])->name('reminders.index');
    Route::post('/reminders', [ReminderController::class, 'store'])->name('reminders.store')->middleware('throttle:10,1');
    Route::patch('/reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update')->middleware('throttle:20,1');
    Route::delete('/reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy')->middleware('throttle:10,1');
    Route::post('/reminders/{reminder}/sent', [ReminderController::class, 'markAsSent'])->name('reminders.sent')->middleware('throttle:10,1');

    // Reports
    Route::get('/reports/finance/pdf', [ReportController::class, 'financePdf'])->name('reports.finance.pdf');
    Route::get('/reports/members/csv', [ReportController::class, 'membersCsv'])->name('reports.members.csv');
    Route::get('/reports/attendance/pdf', [ReportController::class, 'attendancePdf'])->name('reports.attendance.pdf');
    Route::get('/reports/sku/pdf', [ReportController::class, 'skuPdf'])->name('reports.sku.pdf');

    // User Permissions
    Route::middleware(['role:Admin'])->group(function () {
        Route::get('/permissions', [UserPermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions', [UserPermissionController::class, 'store'])->name('permissions.store');
        Route::post('/permissions/sync', [UserPermissionController::class, 'sync'])->name('permissions.sync');
        Route::delete('/permissions/{permission}', [UserPermissionController::class, 'destroy'])->name('permissions.destroy');
    });

    // Trainings
    Route::middleware(['role:Admin,Pembina'])->group(function () {
        Route::get('/trainings', [TrainingController::class, 'index'])->name('trainings.index');
        Route::post('/trainings', [TrainingController::class, 'store'])->name('trainings.store');
        Route::patch('/trainings/{training}', [TrainingController::class, 'update'])->name('trainings.update');
        Route::delete('/trainings/{training}', [TrainingController::class, 'destroy'])->name('trainings.destroy');
    });

    // Health & Safety
    Route::middleware(['role:Admin,Pembina,Pengurus'])->group(function () {
        Route::get('/health/safety/records', [HealthSafetyController::class, 'healthRecords'])->name('health.records.index');
        Route::post('/health/safety/records', [HealthSafetyController::class, 'storeHealthRecord'])->name('health.records.store');
        Route::patch('/health/safety/records/{record}', [HealthSafetyController::class, 'updateHealthRecord'])->name('health.records.update');
        Route::get('/health/safety/checks', [HealthSafetyController::class, 'safetyChecks'])->name('health.checks.index');
        Route::post('/health/safety/checks', [HealthSafetyController::class, 'storeSafetyCheck'])->name('health.checks.store');
    });

    // Candidates
    Route::middleware(['role:Admin,Pembina'])->group(function () {
        Route::get('/candidates', [CandidateController::class, 'index'])->name('candidates.index');
        Route::post('/candidates', [CandidateController::class, 'store'])->name('candidates.store');
        Route::patch('/candidates/{candidate}', [CandidateController::class, 'update'])->name('candidates.update');
        Route::delete('/candidates/{candidate}', [CandidateController::class, 'destroy'])->name('candidates.destroy');
    });

    // System Tools
    Route::middleware(['role:Admin'])->group(function () {
        Route::get('/system/tools/backups', [SystemToolController::class, 'backups'])->name('system.backups.index');
        Route::post('/system/tools/backup', [SystemToolController::class, 'createBackup'])->name('system.backups.create');
        Route::get('/system/tools/webhooks', [SystemToolController::class, 'webhooks'])->name('system.webhooks.index');
        Route::post('/system/tools/webhooks', [SystemToolController::class, 'storeWebhook'])->name('system.webhooks.store');
        Route::post('/system/tools/webhooks/{webhook}/trigger', [SystemToolController::class, 'triggerWebhook'])->name('system.webhooks.trigger');
        Route::delete('/system/tools/webhooks/{webhook}', [SystemToolController::class, 'deleteWebhook'])->name('system.webhooks.destroy');
    });

    // System Points
    Route::middleware(['role:Admin,Pembina,Pengurus'])->group(function () {
        Route::get('/system/points', [SystemPointController::class, 'index'])->name('system.points.index');
        Route::post('/system/points', [SystemPointController::class, 'store'])->name('system.points.store');
        Route::delete('/system/points/{point}', [SystemPointController::class, 'destroy'])->name('system.points.destroy');
    });
});

Route::middleware(['auth', 'role:Alumni'])->prefix('alumni')->name('alumni.')->group(function () {
    Route::get('/dashboard', [AlumniController::class, 'dashboard'])->name('dashboard');
    Route::patch('/profile', [AlumniController::class, 'updateProfile'])->name('profile.update')->middleware('throttle:10,1');
    Route::post('/donations', [AlumniController::class, 'storeDonation'])->name('donations.store')->middleware('throttle:5,1');
    Route::post('/donations/{donation}/cancel', [AlumniController::class, 'cancelDonation'])->name('donations.cancel')->middleware('throttle:5,1');
});