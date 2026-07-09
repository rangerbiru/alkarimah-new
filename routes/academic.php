<?php

use App\Http\Controllers\Academic\AbsenceController as AcademicAbsenceController;
use App\Http\Controllers\Academic\AsramaController as AcademicAsramaController;
use App\Http\Controllers\Academic\BasicDataController as AcademicBasicDataController;
use App\Http\Controllers\Academic\ClassController as AcademicClassController;
use App\Http\Controllers\Academic\ClassHoursController as AcademicClassHoursController;
use App\Http\Controllers\Academic\ClassMonitoringController as AcademicClassMonitoringController;
use App\Http\Controllers\Academic\ClassScheduleController as AcademicClassScheduleController;
use App\Http\Controllers\Academic\ExculController as AcademicExculController;
use App\Http\Controllers\Academic\HalaqahController as AcademicHalaqahController;
use App\Http\Controllers\Academic\ParentController as AcademicParentController;
use App\Http\Controllers\Academic\StudentController as AcademicStudentController;
use App\Http\Controllers\Academic\StudentPermitController as AcademicStudentPermitController;
use App\Http\Controllers\Academic\StudentPermitGroupController as AcademicStudentPermitGroupController;
use App\Http\Controllers\Academic\StudentViolationController as AcademicStudentViolationController;
use App\Http\Controllers\Academic\SubjectController as AcademicSubjectController;
use App\Http\Controllers\Academic\TahfidzController as AcademicTahfidzController;
use Illuminate\Support\Facades\Route;

Route::prefix('absence')->group(function () {
    Route::get('create', [AcademicAbsenceController::class, 'create'])->name('academic.absence.create')->middleware(['role:pegawai', 'accessRights:1']);
    Route::get('report', [AcademicAbsenceController::class, 'report'])->name('academic.absence.report')->middleware('role:admin');
    Route::get('download/excel/report', [AcademicAbsenceController::class, 'downloadExcelReport'])->name('academic.absence.download.excel.report')->middleware('role:admin');
    Route::get('download/pdf/report', [AcademicAbsenceController::class, 'downloadPdfReport'])->name('academic.absence.download.pdf.report')->middleware('role:admin');

    Route::post('/', [AcademicAbsenceController::class, 'store'])->name('academic.absence.store')->middleware(['role:pegawai', 'accessRights:1']);
    Route::post('datatable/report', [AcademicAbsenceController::class, 'datatableReport'])->name('academic.absence.datatable.report')->middleware('role:admin');
    Route::post('get/student', [AcademicAbsenceController::class, 'getStudent'])->name('academic.absence.get.student')->middleware('role:pegawai');

    Route::prefix('type')->group(function () {
        Route::get('/', [AcademicAbsenceController::class, 'type'])->name('academic.absence.type.index')->middleware('role:admin');
        Route::get('create', [AcademicAbsenceController::class, 'createType'])->name('academic.absence.type.create')->middleware('role:admin');
        Route::get('{type}/edit', [AcademicAbsenceController::class, 'editType'])->name('academic.absence.type.edit')->middleware('role:admin');

        Route::post('/', [AcademicAbsenceController::class, 'storeType'])->name('academic.absence.type.store')->middleware('role:admin');
        Route::post('datatable', [AcademicAbsenceController::class, 'datatableType'])->name('academic.absence.type.datatable')->middleware('role:admin');

        Route::put('/{type}', [AcademicAbsenceController::class, 'updateType'])->name('academic.absence.type.update')->middleware('role:admin');

        Route::delete('/', [AcademicAbsenceController::class, 'destroyType'])->name('academic.absence.type.destroy')->middleware('role:admin');
    });
});

Route::post('asrama/datatable', [AcademicAsramaController::class, 'datatable'])->name('academic.asrama.datatable')->middleware('role:admin');
Route::resource('asrama', AcademicAsramaController::class, ['as' => 'academic'])->except(['show'])->middleware('role:admin');

Route::prefix('class')->group(function () {
    Route::post('datatable', [AcademicClassController::class, 'datatable'])->name('academic.class.datatable')->middleware('role:admin');
    Route::post('get/option', [AcademicClassController::class, 'getOption'])->name('academic.class.get.option')->middleware('role:admin,kasir');
    Route::post('get/option/level', [AcademicClassController::class, 'getOptionLevel'])->name('academic.class.get.option.level')->middleware('role:admin,kasir');
    Route::post('get/option/level-class', [AcademicClassController::class, 'getOptionLevelClass'])->name('academic.class.get.option.level-class')->middleware('role:kasir,bendahara');
});
Route::resource('class', AcademicClassController::class, ['as' => 'academic'])->except(['show'])->middleware('role:admin');

Route::resource('class-hours', AcademicClassHoursController::class, ['as' => 'academic'])->except(['show'])->middleware('role:admin');
Route::post('class-hours/datatable', [AcademicClassHoursController::class, 'datatable'])->name('academic.class-hours.datatable');

Route::resource('class-schedule', AcademicClassScheduleController::class, ['as' => 'academic'])->except(['show'])->middleware('role:admin');
Route::get('class-schedule/{id}/manage', [AcademicClassScheduleController::class, 'manageSchedule'])->name('academic.class-schedule.manage')->middleware('role:admin');
Route::post('class-schedule/datatable', [AcademicClassScheduleController::class, 'datatable'])->name('academic.class-schedule.datatable');
Route::post('class-schedule/datatable-manage-schedule/{id}', [AcademicClassScheduleController::class, 'datatableManageSchedule'])->name('academic.class-schedule.datatable-manage-schedule');
// Route::post('/academic/class-schedule/{class}/save-schedule', [AcademicClassScheduleController::class, 'saveSchedule'])
//     ->name('academic.class-schedule.save-schedule');
Route::post('class-schedule/{id}/save', [AcademicClassScheduleController::class, 'saveManageSchedule'])
    ->name('academic.class-schedule.save-manage');

Route::prefix('excul')->group(function () {
    Route::prefix('group')->group(function () {
        Route::get('create/{excul}', [AcademicExculController::class, 'createGroup'])->name('academic.excul.group.create')->middleware('role:admin');
        Route::get('{group}/edit', [AcademicExculController::class, 'editGroup'])->name('academic.excul.group.edit')->middleware('role:admin');
        Route::get('/{excul}', [AcademicExculController::class, 'group'])->name('academic.excul.group.index')->middleware('role:admin');

        Route::post('datatable', [AcademicExculController::class, 'datatableGroup'])->name('academic.excul.group.datatable')->middleware('role:admin');
        Route::post('/', [AcademicExculController::class, 'storeGroup'])->name('academic.excul.group.store')->middleware('role:admin');

        Route::put('/{excul}', [AcademicExculController::class, 'updateGroup'])->name('academic.excul.group.update')->middleware('role:admin');
        Route::delete('/{excul}', [AcademicExculController::class, 'destroyGroup'])->name('academic.excul.group.destroy')->middleware('role:admin');
    });

    Route::post('datatable', [AcademicExculController::class, 'datatable'])->name('academic.excul.datatable')->middleware('role:admin');
});
Route::resource('excul', AcademicExculController::class, ['as' => 'academic'])->except(['show'])->middleware('role:admin');

Route::post('halaqah/datatable', [AcademicHalaqahController::class, 'datatable'])->name('academic.halaqah.datatable')->middleware('role:admin');
Route::resource('halaqah', AcademicHalaqahController::class, ['as' => 'academic'])->except(['show'])->middleware('role:admin');

Route::post('parent/datatable', [AcademicParentController::class, 'datatable'])->name('academic.parent.datatable')->middleware('role:admin');
Route::resource('parent', AcademicParentController::class, ['as' => 'academic'])->except(['show'])->middleware('role:admin');

Route::resource('student', AcademicStudentController::class, ['as' => 'academic'])->except(['index', 'show', 'edit'])->middleware('role:admin');
Route::prefix('student')->group(function () {
    Route::get('/', [AcademicStudentController::class, 'index'])->name('academic.student.index')->middleware('role:admin,orang-tua');
    Route::get('set', [AcademicStudentController::class, 'set'])->name('academic.student.set')->middleware('role:admin');
    Route::get('set/excul', [AcademicStudentController::class, 'setExcul'])->name('academic.student.set.excul')->middleware('role:admin');
    Route::get('change', [AcademicStudentController::class, 'change'])->name('academic.student.change')->middleware('role:admin');
    Route::get('history/displacement/{student}', [AcademicStudentController::class, 'historyDisplacement'])->name('academic.student.history.displacement')->middleware('role:admin');
    Route::get('get/autocomplete', [AcademicStudentController::class, 'getAutocomplete'])->name('academic.student.get.autocomplete')->middleware('role:kasir,penanggung-jawab-tabungan');
    Route::get('/{student}/edit', [AcademicStudentController::class, 'edit'])->name('academic.student.edit')->middleware('role:admin,orang-tua');
    Route::get('/{student}', [AcademicStudentController::class, 'show'])->name('academic.student.show')->middleware('role:orang-tua');

    Route::post('datatable', [AcademicStudentController::class, 'datatable'])->name('academic.student.datatable')->middleware('role:admin');
    Route::post('datatable/set', [AcademicStudentController::class, 'datatableSet'])->name('academic.student.datatable.set')->middleware('role:admin');
    Route::post('datatable/set/excul', [AcademicStudentController::class, 'datatableSetExcul'])->name('academic.student.datatable.set-excul')->middleware('role:admin');
    Route::post('datatable/change', [AcademicStudentController::class, 'datatableChange'])->name('academic.student.datatable.change')->middleware('role:admin');
    Route::post('datatable/history/displacement', [AcademicStudentController::class, 'datatableHistoryDisplacement'])->name('academic.student.datatable.history-displacement')->middleware('role:admin');
    Route::post('set', [AcademicStudentController::class, 'storeSet'])->name('academic.student.store.set')->middleware('role:admin');
    Route::post('set/excul', [AcademicStudentController::class, 'storeSetExcul'])->name('academic.student.store.set-excul')->middleware('role:admin');
    Route::post('change', [AcademicStudentController::class, 'storeChange'])->name('academic.student.store.change')->middleware('role:admin');

    Route::put('parent/{student}', [AcademicStudentController::class, 'updateParent'])->name('academic.student.update.parent')->middleware('role:orang-tua');

    Route::post('/import', [AcademicStudentController::class, 'import'])
        ->name('student.import');
});

Route::resource('student-permit', AcademicStudentPermitController::class, ['as' => 'academic'])->except(['show']);
Route::prefix('student-permit')->group(function () {
    Route::get('/', [AcademicStudentPermitController::class, 'index'])->name('academic.student-permit.index');
    Route::post('datatable', [AcademicStudentPermitController::class, 'datatable'])->name('academic.student-permit.datatable');
    Route::post('get-students-by-group', [AcademicStudentPermitController::class, 'getStudentsByGroup'])->name('academic.student-permit.students');
    Route::post('get-group-by-student', [AcademicStudentPermitController::class, 'getGroupByStudent'])->name('academic.student-permit.groups');
});

Route::resource('student-permit-group', AcademicStudentPermitGroupController::class, ['as' => 'academic'])->except(['show'])->middleware('role:admin');
Route::prefix('student-permit-group')->group(function () {
    Route::get('/', [AcademicStudentPermitGroupController::class, 'index'])->name('academic.student-permit-group.index')->middleware('role:admin');
    Route::post('datatable', [AcademicStudentPermitGroupController::class, 'datatable'])->name('academic.student-permit-group.datatable')->middleware('role:admin');
});

Route::prefix('tahfidz')->group(function () {
    Route::get('/', [AcademicTahfidzController::class, 'index'])->name('academic.tahfidz.index');
    Route::post('datatable', [AcademicTahfidzController::class, 'datatable'])->name('academic.tahfidz.datatable');
    Route::get('print/{id}/{jenis_kaldik}', [AcademicTahfidzController::class, 'printTahfidz'])->name('academic.tahfidz.print');
});

Route::resource('basic', AcademicBasicDataController::class)->names('academic.basic');
Route::post('basic/datatable', [AcademicBasicDataController::class, 'datatable'])->name('academic.basic.datatable');

Route::resource('subject', AcademicSubjectController::class)->names('academic.subject');
Route::post('subject/datatable', [AcademicSubjectController::class, 'datatable'])->name('academic.subject.datatable');

Route::resource('monitoring', AcademicClassMonitoringController::class)->names('academic.monitoring');
Route::get('/monitoring/data', [AcademicClassMonitoringController::class, 'getMonitoringData'])
    ->name('academic.monitoring.data');

Route::resource('violation', AcademicStudentViolationController::class)->names('academic.violation')->except(['show']);
Route::post('get-students', [AcademicStudentViolationController::class, 'getStudents'])->name('academic.violation.students');
Route::get('violation/types', [AcademicStudentViolationController::class, 'getViolationTypes'])->name('academic.violation.types');

Route::post('violation/datatable', [AcademicStudentViolationController::class, 'datatable'])->name('academic.violation.datatable');
