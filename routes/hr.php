<?php

use App\Http\Controllers\Hr\AllowanceController as HrAllowanceController;
use App\Http\Controllers\Hr\AllowedSubmissionEmployeeController as HrAllowedSubmissionEmployeeController;
use App\Http\Controllers\Hr\AttendanceGroupController;
use App\Http\Controllers\Hr\AttendanceLocationController;
use App\Http\Controllers\Hr\AttendanceMemberController;
use App\Http\Controllers\Hr\AttendanceReportController;
use App\Http\Controllers\Hr\DepartmentController as HrDepartmentController;
use App\Http\Controllers\Hr\EmployeeActivityController as HrEmployeeActivityController;
use App\Http\Controllers\Hr\EmployeeController as HrEmployeeController;
use App\Http\Controllers\Hr\InventoryItemController as HrInventoryItemController;
use App\Http\Controllers\Hr\ItemCategoryController as HrItemCategoryController;
use App\Http\Controllers\Hr\ItemsController as HrItemsController;
use App\Http\Controllers\Hr\LocationMasterController as HrLocationMasterController;
use App\Http\Controllers\Hr\PermitTypeController as HrPermitTypeController;
use App\Http\Controllers\Hr\PositionController as HrPositionController;
use App\Http\Controllers\Hr\UnitMasterController as HrUnitMasterController;
use App\Http\Controllers\Hr\ViolationTypeMasterController as HrViolationTypeMasterController;
use Illuminate\Support\Facades\Route;

Route::post('allowance/datatable', [HrAllowanceController::class, 'datatable'])->name('hr.allowance.datatable')->middleware('role:bendahara');
Route::resource('allowance', HrAllowanceController::class, ['as' => 'hr'])->except(['show'])->middleware('role:bendahara');

Route::prefix('employee')->group(function () {
    Route::get('rights/{employee}', [HrEmployeeController::class, 'rights'])->name('hr.employee.rights')->middleware('role:admin');

    Route::post('datatable', [HrEmployeeController::class, 'datatable'])->name('hr.employee.datatable')->middleware('role:admin');
    Route::post('rights/{employee}', [HrEmployeeController::class, 'storeRights'])->name('hr.employee.store.rights')->middleware('role:admin');

    Route::post('/import', [HrEmployeeController::class, 'import'])
        ->name('employee.import');
});
Route::resource('employee', HrEmployeeController::class, ['as' => 'hr'])->except(['show'])->middleware('role:admin');

Route::post('position/datatable', [HrPositionController::class, 'datatable'])->name('hr.position.datatable')->middleware('role:admin');
Route::resource('position', HrPositionController::class, ['as' => 'hr'])->except(['show'])->middleware('role:admin');

Route::prefix('attendance')->name('hr.attendance.')->middleware('role:admin')->group(function () {
    // Attendance Group
    Route::resource('group', AttendanceGroupController::class)->except(['show'])->names('group');
    Route::put('/group/{id}/update-time', [AttendanceGroupController::class, 'updateTime'])->name('group.update-time');
    Route::put('/group/{id}/update-time-shift', [AttendanceGroupController::class, 'updateTimeShift'])->name('group.update-time-shift');
    Route::post('group/datatable', [AttendanceGroupController::class, 'datatable'])->name('group.datatable');
    // Route::put('group/{id}', [AttendanceGroupController::class, 'updateShift'])->name('hr.attendance.group.update');

    // Attendance Member
    Route::resource('member', AttendanceMemberController::class)->names('member');
    Route::post('member/datatable', [AttendanceMemberController::class, 'datatable'])->name('member.datatable');
    Route::post('member/data-employee', [AttendanceMemberController::class, 'dataEmployee'])->name('member.data-employee');

    Route::resource('location', AttendanceLocationController::class)->names('location');
    Route::post('location/datatable', [AttendanceLocationController::class, 'datatable'])->name('location.datatable');

    Route::resource('report', AttendanceReportController::class)->names('report');
    Route::post('report/datatable', [AttendanceReportController::class, 'datatable'])->name('report.datatable');
});

Route::patch('hr/attendance/group/{id}/shift', [AttendanceGroupController::class, 'updateShift'])
    ->name('hr.attendance.group.update.shift');

Route::resource('employee-activity', HrEmployeeActivityController::class)->names('hr.employee-activity')->middleware('role:admin');
Route::post('employee-activity/datatable', [HrEmployeeActivityController::class, 'datatable'])->name('hr.employee-activity.datatable');

Route::resource('allowed-submission', HrAllowedSubmissionEmployeeController::class)->names('hr.allowed-submission')->middleware('role:admin');
Route::post('allowed-submission/datatable', [HrAllowedSubmissionEmployeeController::class, 'datatable'])->name('hr.allowed-submission.datatable');

Route::resource('department', HrDepartmentController::class)->names('hr.department')->middleware('role:admin');
Route::post('department/datatable', [HrDepartmentController::class, 'datatable'])->name('hr.department.datatable')->middleware('role:admin');
Route::post('department/data-employee', [HrDepartmentController::class, 'dataEmployee'])->name('hr.department.data-employee');

Route::resource('permit-type', HrPermitTypeController::class)->names('hr.permit-type')->middleware('role:admin');
Route::post('permit-type/datatable', [HrPermitTypeController::class, 'datatable'])->name('hr.permit-type.datatable')->middleware('role:admin');

Route::resource('inventory-item', HrInventoryItemController::class)->names('hr.inventory-item')->middleware('role:admin');
Route::post('inventory-item/datatable', [HrInventoryItemController::class, 'datatable'])->name('hr.inventory-item.datatable')->middleware('role:admin');
Route::get('/inventory-item/{id}/modal', [HrInventoryItemController::class, 'showModal'])->name('hr.inventory-item.modal');

Route::resource('item', HrItemsController::class)->names('hr.item')->middleware('role:admin');
Route::post('item/datatable', [HrItemsController::class, 'datatable'])->name('hr.item.datatable')->middleware('role:admin');
Route::get('/item/{id}/modal', [HrItemsController::class, 'showModal'])->name('hr.item.modal');

// Master
Route::resource('item-category', HrItemCategoryController::class)->names('hr.item-category')->middleware('role:admin');
Route::post('item-category/datatable', [HrItemCategoryController::class, 'datatable'])->name('hr.item-category.datatable')->middleware('role:admin');

Route::resource('location', HrLocationMasterController::class)->names('hr.location')->middleware('role:admin');
Route::post('location/datatable', [HrLocationMasterController::class, 'datatable'])->name('hr.location.datatable')->middleware('role:admin');

Route::resource('unit', HrUnitMasterController::class)->names('hr.unit')->middleware('role:admin');
Route::post('unit/datatable', [HrUnitMasterController::class, 'datatable'])->name('hr.unit.datatable')->middleware('role:admin');

Route::resource('violation', HrViolationTypeMasterController::class)->names('hr.violation')->middleware('role:admin');
Route::post('violation/datatable', [HrViolationTypeMasterController::class, 'datatable'])->name('hr.violation.datatable')->middleware('role:admin');
