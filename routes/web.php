<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\IconController;
use App\Http\Controllers\MootaController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\Setting\AppController as SettingAppController;
use App\Http\Controllers\Setting\YearController as SettingYearController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsappController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'index'])->name('base');
Route::get('register', [AuthController::class, 'register'])->name('auth.register');
Route::get('register/verification/{parent}', [AuthController::class, 'registerVerification'])->name('auth.register.verification');
Route::get('register/password/{parent}', [AuthController::class, 'registerPassword'])->name('auth.register.password');
Route::get('forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
Route::get('forgot-password/verification/{user}', [AuthController::class, 'forgotPasswordVerification'])->name('auth.forgot-password.verification');
Route::get('reset-password/{token}', [AuthController::class, 'resetPassword'])->name('auth.reset-password');
Route::get('logout', [AuthController::class, 'logout'])->name('auth.logout');
Route::get('privacy-policy', [PageController::class, 'privacyPolicy']);

Route::post('register', [AuthController::class, 'storeRegister'])->name('auth.store.register');
Route::post('register/verification/{parent}', [AuthController::class, 'storeRegisterVerification'])->name('auth.store.register.verification');
Route::post('register/password/{parent}', [AuthController::class, 'storeRegisterPassword'])->name('auth.store.register-password');
Route::post('forgot-password', [AuthController::class, 'storeForgotPassword'])->name('auth.store.forgot-password');
Route::post('forgot-password/verification/{user}', [AuthController::class, 'storeForgotPasswordVerification'])->name('auth.store.forgot-password.verification');
Route::post('reset-password/{user}', [AuthController::class, 'storeResetPassword'])->name('auth.store.reset-password');
Route::post('authenticate', [AuthController::class, 'authenticate'])->name('auth.authenticate');
Route::post('moota/notification/{bank}', [MootaController::class, 'notification'])->whereIn('bank', ['bsi', 'bni'])->name('moota.notification');
Route::get('captcha-refresh', [CaptchaController::class, 'refresh'])->name('captcha.refresh');

Route::group(['middleware' => ['auth', 'initialize.backend']], function () {
    Route::prefix('academic')->group(function () {
        require_once __DIR__.'/academic.php';
    });

    Route::prefix('employee')->group(function () {
        require_once __DIR__.'/employee.php';
    });

    Route::prefix('finance')->group(function () {
        require_once __DIR__.'/finance.php';
    });

    Route::prefix('hr')->group(function () {
        require_once __DIR__.'/hr.php';
    });

    Route::prefix('service')->group(function () {
        require_once __DIR__.'/service.php';
    });

    Route::prefix('attachment')->group(function () {
        Route::get('get/{attachment}', [AttachmentController::class, 'get'])->name('attachment.get');
        Route::get('download/{attachment}', [AttachmentController::class, 'download'])->name('attachment.download');
        Route::post('temporary', [AttachmentController::class, 'storeTemporary'])->name('attachment.store.temporary');
    });

    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::post('datatable/withdrawal', [DashboardController::class, 'datatableWithdrawal'])->name('dashboard.datatable.withdrawal');
        Route::post('datatable/bill-not-paid', [DashboardController::class, 'datatableBillNotPaid'])->name('dashboard.datatable.bill-not-paid');
        Route::post('get/count', [DashboardController::class, 'getCount'])->name('dashboard.get.count')->middleware('role:kasir');
        Route::post('get/payment-progress', [DashboardController::class, 'getPaymentProgress'])->name('dashboard.get.payment-progress')->middleware('role:kasir');
        Route::post('get/receipt', [DashboardController::class, 'getReceipt'])->name('dashboard.get.receipt')->middleware('role:kasir');

        Route::post('datatable/employee', [DashboardController::class, 'datatableAttendance'])->name('dashboard.datatable.attendance');
        Route::get('/attendance-summary/data', [DashboardController::class, 'getData'])->name('attendance.summary.data');
        Route::get('/attendance/positions', [DashboardController::class, 'getPositions'])->name('attendance.positions');
        Route::get('/attendance/export', [DashboardController::class, 'exportExcel'])->name('attendance.export');

        // Employee Attendance
        Route::post('attendance/in', [AttendanceController::class, 'attendanceIn'])->name('dashboard.employee.attendanceIn')->middleware('role:kasir,pegawai,wali-kelas,admin,penanggungJawabTabungan');
        Route::post('attendance/out', [AttendanceController::class, 'attendanceOut'])->name('dashboard.employee.attendanceOut')->middleware('role:kasir,pegawai,wali-kelas,admin,penanggungJawabTabungan');
        Route::post('attendance/reason', [AttendanceController::class, 'storeReason'])->name('dashboard.employee.attendance.reason');
    });

    Route::post('branch/datatable', [BranchController::class, 'datatable'])->name('branch.datatable');
    Route::resource('branch', BranchController::class)->except(['show']);

    Route::post('icon/datatable', [IconController::class, 'datatable'])->name('icon.datatable');

    Route::post('region/option', [RegionController::class, 'option'])->name('region.option');

    Route::prefix('setting')->group(function () {
        Route::get('/', [SettingAppController::class, 'index'])->name('setting.index');
        Route::put('{setting}', [SettingAppController::class, 'update'])->name('setting.update');

        Route::prefix('year')->group(function () {
            Route::get('/', [SettingYearController::class, 'index'])->name('setting.year.index');
            Route::post('datatable', [SettingYearController::class, 'datatable'])->name('setting.year.datatable');
            Route::post('store', [SettingYearController::class, 'store'])->name('setting.year.store');
            Route::post('status/{year}', [SettingYearController::class, 'updateStatus'])->name('setting.year.update.status');
            Route::post('update/{year}', [SettingYearController::class, 'update'])->name('setting.year.update');
        });
    });

    Route::prefix('user')->group(function () {
        Route::get('{role}', [UserController::class, 'index'])->where('role', '[bendahara|kasir|wali\-kelas|penanggung\-jawab\-tabungan]+')->name('user.index');
        Route::get('create/{role}', [UserController::class, 'create'])->where('role', '[bendahara|kasir|wali\-kelas|penanggung\-jawab\-tabungan]+')->name('user.create');
        Route::post('datatable', [UserController::class, 'datatable'])->name('user.datatable');
    });
    Route::resource('user', UserController::class)->except(['index', 'create', 'show']);

    Route::post('whatsapp/send/bill-due-date', [WhatsappController::class, 'sendBillDueDate'])->name('whatsapp.send.bill-due-date');

    Route::get('profile', [UserController::class, 'editProfile'])->name('user.profile');
    Route::put('profile', [UserController::class, 'updateProfile'])->name('user.profile.update');

    Route::get('change-password', [UserController::class, 'editPassword'])->name('user.password');
    Route::put('change-password', [UserController::class, 'updatePassword'])->name('user.password.update');
});
