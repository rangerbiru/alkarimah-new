<?php

use App\Http\Controllers\Employee\ActivityReportsController;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\AttendanceReportController;
use App\Http\Controllers\Employee\HafalanController;
use App\Http\Controllers\Employee\StudentPermitController as EmployeeStudentPermitController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Employee\TahfidzController;
use App\Http\Controllers\Employee\TeachingScheduleController;
use App\Http\Controllers\Employee\CommitteeActivityController;
use App\Http\Controllers\Employee\EmployeePermitController;
use App\Http\Controllers\Employee\LunchReportController;
use App\Http\Controllers\Employee\SubmissionController;
use App\Http\Controllers\Employee\SubmissionItemController;
use App\Http\Controllers\Employee\LogisticsInventoryController;
use App\Http\Middleware\CheckAllowedSubmissionEmployee;
use App\Http\Middleware\CheckPimpinanEmployee;
use App\Models\Employee;
use Illuminate\Http\Client\Request;

Route::resource('tahfidz', TahfidzController::class)->names('employee.tahfidz');

Route::resource('hafalan', HafalanController::class)->names('employee.hafalan');
Route::prefix('hafalan')->group(function () {
    Route::get('/get-halaman/{id}', [HafalanController::class, 'getHalamanByJuz']);
    Route::get('/get-baris/{id}', [HafalanController::class, 'getBarisByHalaman']);
    Route::post('/check-student', [HafalanController::class, 'checkStudent'])->name('employee.hafalan.checkStudent');
});
Route::delete('/employee/hafalan/delete', [HafalanController::class, 'destroy'])->name('employee.hafalan.custom-destroy');
Route::get('/data-hafalan', [HafalanController::class, 'datatable'])->name('employee.hafalan.datatable');
Route::get('/data-hafalan/{id}/{jenis_kaldik}', [HafalanController::class, 'datatableById'])->name('employee.hafalan.datatableById');


Route::get('/data-absensi-tahfidz', [TahfidzController::class, 'anyDataAbsensiTahfidz'])->name('employee.tahfidz.datatable');
Route::get('/get-siswa-by-pengampu', [TahfidzController::class, 'getSiswaByPengampu'])->name('employee.tahfidz.get-siswa-by-pengampu');

Route::get('/process/{id}', [TahfidzController::class, 'process'])->name('employee.tahfidz.process');
Route::get('/pertemuan-terpakai/{id}', [TahfidzController::class, 'getPertemuanTerpakai'])->name('employee.tahfidz.pertemuan-terpakai');


// Absensi KBM Tahfidz
Route::prefix('process')->group(function () {
    Route::get('/get-target-sabqi/{id}', [TahfidzController::class, 'getTargetMurojaahSabqi']);
    Route::get('/get-target-manzil/{id}', [TahfidzController::class, 'getTargetMurojaahManzil']);
    Route::get('/get-target-ziyadah/{id}', [TahfidzController::class, 'getTargetZiyadah']);
    Route::get('/get-halaman/{id}', [TahfidzController::class, 'getHalamanByJuz']);
    Route::get('/get-baris/{id}', [TahfidzController::class, 'getBarisByHalaman']);

    Route::post('/store-proses/{id}', [TahfidzController::class, 'storeProsesAbsensi'])->name('employee.tahfidz.process.store');
    Route::delete('/destroy/{id}', [TahfidzController::class, 'destroyProsesAbsensi'])->name('employee.tahfidz.process.destroy');

    Route::post('/send-message/{id}', [TahfidzController::class, 'sendMessage'])->name('employee.tahfidz.process.send-message');
});

Route::get('/get-id-surat-murojaah', [TahfidzController::class, 'getIdSuratMurojaah'])->name('employee.tahfidz.process.get-id-surat-murojaah');
Route::get('/get-id-surat', [TahfidzController::class, 'getIdSurat'])->name('employee.tahfidz.process.get-id-surat');
Route::get('/datatable/{id}', [TahfidzController::class, 'anyDataProses'])->name('employee.tahfidz.process.datatable');

Route::resource('student-permit', EmployeeStudentPermitController::class)->names('employee.student-permit');
Route::prefix('student-permit')->group(function () {
    Route::get('/', [EmployeeStudentPermitController::class, 'index'])->name('employee.student-permit.index');
    // Route::post('store', [EmployeeStudentPermitController::class, 'store'])->name('employee.student-permit.store');
    Route::post('datatable', [EmployeeStudentPermitController::class, 'datatable'])->name('employee.student-permit.datatable');
    Route::post('datatable-permit', [EmployeeStudentPermitController::class, 'datatablePermit'])->name('employee.student-permit.datatable-permit');
    // Route::post('get-students', [EmployeeStudentPermitController::class, 'getStudents'])->name('employee.student-permit.getStudents');
    Route::post('get-students-by-group', [EmployeeStudentPermitController::class, 'getStudentsByGroup'])->name('employee.student-permit.students');
    Route::post('get-group-by-student', [EmployeeStudentPermitController::class, 'getGroupByStudent'])->name('employee.student-permit.groups');
    Route::put('approve/{id}', [EmployeeStudentPermitController::class, 'approve'])->name('employee.student-permit.approve');
    Route::put('reject/{id}', [EmployeeStudentPermitController::class, 'reject'])->name('employee.student-permit.reject');
});

Route::prefix('attendance')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('employee.attendance.index');
    Route::post('datatable', [AttendanceController::class, 'datatable'])->name('employee.attendance.datatable');

    Route::get('/export/excel', [AttendanceController::class, 'exportExcel'])->name('employee.attendance.export.excel');
    Route::get('/export/pdf', [AttendanceController::class, 'exportPdf'])->name('employee.attendance.export.pdf');
});

Route::resource('teaching-schedule', TeachingScheduleController::class)->names('employee.teaching-schedule');
Route::post('/teaching-schedule/journal', [TeachingScheduleController::class, 'storeJournal'])
    ->name('employee.teaching-schedule.journal.store');


Route::resource('activity-report', ActivityReportsController::class)->names('employee.activity-report')->except(['show']);
Route::post('/activity-report/datatable', [ActivityReportsController::class, 'datatable'])->name('employee.activity-report.datatable');
Route::post('/employee/activity-report/{id}/comment', [ActivityReportsController::class, 'storeComment'])
    ->name('employee.activity-report.comment');
Route::get('/activity-report/export', [ActivityReportsController::class, 'exportExcel'])
    ->name('employee.activity-report.export');

Route::resource('committee-activity', CommitteeActivityController::class)->names('employee.committee-activity')->except(['show']);
Route::post('/committee-activity/datatable', [CommitteeActivityController::class, 'datatable'])->name('employee.committee-activity.datatable');
// Route::get('committee-activity/get-employees', [CommitteeActivityController::class, 'getEmployees'])->name('employee.committee-activity.get-employees');
Route::post('data-employee', [CommitteeActivityController::class, 'dataEmployee'])->name('employee.committee-activity.data-employee');

Route::delete('committee-activity/document/{document}', [CommitteeActivityController::class, 'destroyDocument'])
    ->name('employee.committee-activity.document.destroy');
// routes/web.php
Route::get('committee-activity/{committeeActivity}', [CommitteeActivityController::class, 'show'])
    ->name('employee.committee-activity.show');

Route::middleware([CheckAllowedSubmissionEmployee::class])->group(function () {
    Route::resource('submission', SubmissionController::class)->names('employee.submission')->except(['show']);
    Route::resource('submission/item', SubmissionItemController::class)->names('employee.submission.item')->except(['show']);
    Route::post('submission/{id}/approve', [SubmissionController::class, 'approve'])->name('employee.submission.approve');
    Route::post('submission/{id}/reject', [SubmissionController::class, 'reject'])->name('employee.submission.reject');
});

Route::resource('lunch-report', LunchReportController::class)->names('employee.lunch-report')->except(['show']);
Route::post('/lunch-report/datatable', [LunchReportController::class, 'datatable'])->name('employee.lunch-report.datatable');

Route::resource('permit', EmployeePermitController::class)->names('employee.permit');
Route::post('permit/datatable', [EmployeePermitController::class, 'datatable'])->name('employee.permit.datatable');
Route::post('permit/{id}/approve', [EmployeePermitController::class, 'approve'])->name('employee.permit.approve');
Route::post('permit/{id}/reject', [EmployeePermitController::class, 'reject'])->name('employee.permit.reject');

Route::resource('inventory', LogisticsInventoryController::class)->names('employee.inventory')->except(['show']);
Route::post('/inventory/datatable', [LogisticsInventoryController::class, 'datatable'])->name('employee.inventory.datatable');
Route::get('/item/{id}/modal', [LogisticsInventoryController::class, 'showModal'])->name('employee.inventory.modal');
Route::get('/inventory/{id}/input', [LogisticsInventoryController::class, 'inputInventory'])->name('employee.inventory.input');
Route::post('/inventory/{id}/input', [LogisticsInventoryController::class, 'inputInventory']);


Route::middleware([CheckPimpinanEmployee::class])->group(function () {
    Route::resource('attendance-report', AttendanceReportController::class)->names('employee.attendance-report')->except(['show']);
    Route::post('datatable/employee', [AttendanceReportController::class, 'datatable'])->name('employee.attendance-report.datatable');
    Route::get('/attendance-summary/data', [AttendanceReportController::class, 'getData'])->name('employee.attendance-report.summary.data');
    Route::get('/attendance/positions', [AttendanceReportController::class, 'getPositions'])->name('employee.attendance-report.positions');
    Route::get('/attendance/export', [AttendanceReportController::class, 'exportExcel'])->name('employee.attendance-report.export');
});
