<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

Breadcrumbs::for('dashboard', function (BreadcrumbTrail $trail) {
    $trail->push(__('label.dashboard'), route('dashboard.index'));
});

Breadcrumbs::for('academic/absence/report', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.absence'), route('academic.absence.report'));
});

Breadcrumbs::for('academic/absence/type', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.absence_type'), route('academic.absence.type.index'));
});

Breadcrumbs::for('academic/absence/type/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/absence/type');
    $trail->push(__('label.create'), route('academic.absence.type.create'));
});

Breadcrumbs::for('academic/absence/type/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/absence/type');
    $trail->push(__('label.edit'), route('academic.absence.type.edit', $id));
});

Breadcrumbs::for('academic/asrama', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.asrama'), route('academic.asrama.index'));
});

Breadcrumbs::for('academic/asrama/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/asrama');
    $trail->push(__('label.create'), route('academic.asrama.create'));
});

Breadcrumbs::for('academic/asrama/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/asrama');
    $trail->push(__('label.edit'), route('academic.asrama.edit', $id));
});

Breadcrumbs::for('academic/class', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.class'), route('academic.class.index'));
});

Breadcrumbs::for('academic/class/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/class');
    $trail->push(__('label.create'), route('academic.class.create'));
});

Breadcrumbs::for('academic/class/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/class');
    $trail->push(__('label.edit'), route('academic.class.edit', $id));
});

Breadcrumbs::for('academic/excul', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.excul'), route('academic.excul.index'));
});

Breadcrumbs::for('academic/excul/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/excul');
    $trail->push(__('label.create'), route('academic.excul.create'));
});

Breadcrumbs::for('academic/excul/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/excul');
    $trail->push(__('label.edit'), route('academic.excul.edit', $id));
});

Breadcrumbs::for('academic/excul/group', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/excul');
    $trail->push(__('label.group'), route('academic.excul.group.index', $id));
});

Breadcrumbs::for('academic/excul/group/create', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/excul/group');
    $trail->push(__('label.create'), route('academic.excul.group.create', $id));
});

Breadcrumbs::for('academic/excul/group/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/excul/group');
    $trail->push(__('label.edit'), route('academic.excul.group.edit', $id));
});

Breadcrumbs::for('academic/halaqah', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.halaqah'), route('academic.halaqah.index'));
});

Breadcrumbs::for('academic/halaqah/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/halaqah');
    $trail->push(__('label.create'), route('academic.halaqah.create'));
});

Breadcrumbs::for('academic/halaqah/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/halaqah');
    $trail->push(__('label.edit'), route('academic.halaqah.edit', $id));
});

Breadcrumbs::for('academic/parent', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.parent'), route('academic.parent.index'));
});

Breadcrumbs::for('academic/parent/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/parent');
    $trail->push(__('label.create'), route('academic.parent.create'));
});

Breadcrumbs::for('academic/parent/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/parent');
    $trail->push(__('label.edit'), route('academic.parent.edit', $id));
});

Breadcrumbs::for('academic/student', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.student'), route('academic.student.index'));
});

Breadcrumbs::for('academic/student/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/student');
    $trail->push(__('label.create'), route('academic.student.create'));
});

Breadcrumbs::for('academic/student/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/student');
    $trail->push(__('label.edit'), route('academic.student.edit', $id));
});

Breadcrumbs::for('academic/student/set', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/student');
    $trail->push(__('label.set_class'), route('academic.student.set'));
});

Breadcrumbs::for('academic/student/set/excul', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/student');
    $trail->push(__('label.set_excul'), route('academic.student.set.excul'));
});

Breadcrumbs::for('academic/student/change', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/student');
    $trail->push(__('label.change_class'), route('academic.student.change'));
});

Breadcrumbs::for('academic/student/history-displacement', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/student');
    $trail->push(__('label.move_history'), route('academic.student.history.displacement', $id));
});

// Add student permit breadcrumb
Breadcrumbs::for('academic/student-permit', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.student_permit'), route('academic.student-permit.index'));
});

Breadcrumbs::for('academic/student-permit/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/student-permit');
    $trail->push(__('label.create'), route('academic.student-permit.create'));
});

Breadcrumbs::for('academic/student-permit/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/student-permit');
    $trail->push(__('label.edit'), route('academic.student-permit.edit', $id));
});

// Add student permit group breadcrumb
Breadcrumbs::for('academic/student-permit-group', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.student_permit_group'), route('academic.student-permit-group.index'));
});

Breadcrumbs::for('academic/student-permit-group/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/student-permit-group');
    $trail->push(__('label.create'), route('academic.student-permit-group.create'));
});

Breadcrumbs::for('academic/student-permit-group/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/student-permit-group');
    $trail->push(__('label.edit'), route('academic.student-permit-group.edit', $id));
});

// End student permit group breadcrumb

Breadcrumbs::for('academic/subject', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.subject'), route('academic.subject.index'));
});

Breadcrumbs::for('academic/subject/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/subject');
    $trail->push(__('label.create'), route('academic.subject.create'));
});

Breadcrumbs::for('academic/subject/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/subject');
    $trail->push(__('label.edit'), route('academic.subject.edit', $id));
});

Breadcrumbs::for('academic/basic', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.basic_data'), route('academic.basic.index'));
});

Breadcrumbs::for('academic/basic/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/basic');
    $trail->push(__('label.create'), route('academic.basic.create'));
});

Breadcrumbs::for('academic/basic/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/basic');
    $trail->push(__('label.edit'), route('academic.basic.edit', $id));
});

Breadcrumbs::for('academic/class-hours', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.manage_class_hours'), route('academic.class-hours.index'));
});

Breadcrumbs::for('academic/class-hours/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/class-hours');
    $trail->push(__('label.create'), route('academic.class-hours.create'));
});

Breadcrumbs::for('academic/class-hours/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/class-hours');
    $trail->push(__('label.edit'), route('academic.class-hours.edit', $id));
});

Breadcrumbs::for('academic/class-schedule', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.manage_lesson_schedule'), route('academic.class-schedule.index'));
});

Breadcrumbs::for('academic/class-schedule/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/class-schedule');
    $trail->push(__('label.create'), route('academic.class-schedule.create'));
});

Breadcrumbs::for('academic/class-schedule/manage', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/class-schedule');
    $trail->push(__('label.edit'), route('academic.class-schedule.manage', $id));
});

Breadcrumbs::for('academic/monitoring', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.class_monitoring'), route('academic.monitoring.index'));
});

Breadcrumbs::for('academic/violation', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.student_violation'), route('academic.violation.index'));
});

Breadcrumbs::for('academic/violation/create', function (BreadcrumbTrail $trail) {
    $trail->parent('academic/violation');
    $trail->push(__('label.create'), route('academic.violation.create'));
});

Breadcrumbs::for('academic/violation/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('academic/violation');
    $trail->push(__('label.edit'), route('academic.violation.edit', $id));
});

Breadcrumbs::for('bill', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.bill'), route('finance.bill.type.index'));
});

Breadcrumbs::for('bill/create', function (BreadcrumbTrail $trail) {
    $trail->parent('bill');
    $trail->push(__('label.create'), route('finance.bill.type.create'));
});

Breadcrumbs::for('bill/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('bill');
    $trail->push(__('label.edit'), route('finance.bill.type.edit', $id));
});

Breadcrumbs::for('branch', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.branch'), route('branch.index'));
});

Breadcrumbs::for('branch/create', function (BreadcrumbTrail $trail) {
    $trail->parent('branch');
    $trail->push(__('label.create'), route('branch.create'));
});

Breadcrumbs::for('donation', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.donation'), route('finance.donation.index'));
});

Breadcrumbs::for('donation/create', function (BreadcrumbTrail $trail) {
    $trail->parent('donation');
    $trail->push(__('label.create'), route('finance.donation.create'));
});

Breadcrumbs::for('donation/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('donation');
    $trail->push(__('label.edit'), route('finance.donation.edit', $id));
});

Breadcrumbs::for('branch/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('branch');
    $trail->push(__('label.edit'), route('branch.edit', $id));
});

Breadcrumbs::for('finance/bill/discount', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.bill').' - '.__('label.discount'), route('finance.bill.discount.index'));
});

Breadcrumbs::for('finance/bill/discount/create', function (BreadcrumbTrail $trail) {
    $trail->parent('finance/bill/discount');
    $trail->push(__('label.create'), route('finance.bill.discount.create'));
});

Breadcrumbs::for('finance/bill/discount/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('finance/bill/discount');
    $trail->push(__('label.edit'), route('finance.bill.discount.edit', $id));
});

Breadcrumbs::for('finance/bill/type', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.bill').' - '.__('label.type'), route('finance.bill.type.index'));
});

Breadcrumbs::for('finance/bill/type/create', function (BreadcrumbTrail $trail) {
    $trail->parent('finance/bill/type');
    $trail->push(__('label.create'), route('finance.bill.type.create'));
});

Breadcrumbs::for('finance/bill/type/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('finance/bill/type');
    $trail->push(__('label.edit'), route('finance.bill.type.edit', $id));
});

Breadcrumbs::for('finance/bill/setup', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.bill').' - '.__('label.setup'), route('finance.bill.setup.index'));
});

Breadcrumbs::for('finance/bill/setup/setting', function (BreadcrumbTrail $trail) {
    $trail->parent('finance/bill/setup');
    $trail->push(__('label.setting'), route('finance.bill.setup.setting'));
});

Breadcrumbs::for('finance/bill/setup/create', function (BreadcrumbTrail $trail) {
    $trail->parent('finance/bill/setup');
    $trail->push(__('label.create'), route('finance.bill.setup.create'));
});

Breadcrumbs::for('finance/bill/setup/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('finance/bill/setup');
    $trail->push(__('label.edit'), route('finance.bill.setup.edit', $id));
});

Breadcrumbs::for('finance/payroll/setup', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.payroll_setup'), route('finance.payroll.setup'));
});

Breadcrumbs::for('finance/payroll/setup/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('dashboard');
    $trail->push(__('label.payroll_setup'), route('finance.payroll.edit.setup', $id));
});

Breadcrumbs::for('finance/payroll/slip', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.salary_slip'), route('finance.payroll.slip'));
});

Breadcrumbs::for('finance/payroll/show/slip', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('dashboard');
    $trail->push(__('label.salary_slip'), route('finance.payroll.show.slip', $id));
});

Breadcrumbs::for('finance/transaction/bill', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.payment'), route('finance.transaction.bill.index'));
});

Breadcrumbs::for('finance/transaction/cash', function (BreadcrumbTrail $trail, $render) {
    $trail->parent('dashboard');
    $trail->push(__('label.cash_deposit'), route('finance.transaction.cash', $render));
});

Breadcrumbs::for('finance/transaction/cash/create', function (BreadcrumbTrail $trail) {
    $trail->parent('finance/transaction/cash', 'waiting');
    $trail->push(__('label.create'), route('finance.transaction.create.cash'));
});

Breadcrumbs::for('finance/transaction/unique-code', function (BreadcrumbTrail $trail, $render) {
    $trail->parent('dashboard');
    $trail->push(__('label.unique_code_deposit'), route('finance.transaction.unique-code', $render));
});

Breadcrumbs::for('finance/transaction/unique-code/create', function (BreadcrumbTrail $trail) {
    $trail->parent('finance/transaction/unique-code', 'waiting');
    $trail->push(__('label.create'), route('finance.transaction.create.unique-code'));
});

Breadcrumbs::for('finance/transaction/history', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.history'), route('finance.transaction.history'));
});

Breadcrumbs::for('finance/transaction/bill/show', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('finance/transaction/history');
    $trail->push(__('label.detail'), route('finance.transaction.bill.show', $id));
});

Breadcrumbs::for('finance/transaction/pending', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.pending'), route('finance.transaction.pending'));
});

Breadcrumbs::for('finance/report/bill-student', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.bill_per_student'), route('finance.report.bill-student'));
});

Breadcrumbs::for('finance/report/bill-not-paid', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.bill_not_paid'), route('finance.report.bill-not-paid'));
});

Breadcrumbs::for('finance/report/bill-progress', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.bill_progress'), route('finance.report.bill-progress'));
});

Breadcrumbs::for('finance/report/bill-total', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.bill_total'), route('finance.report.bill-total'));
});

Breadcrumbs::for('finance/report/donation', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.donation'), route('finance.report.donation'));
});

Breadcrumbs::for('finance/report/payment-method', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.payment_method'), route('finance.report.payment-method'));
});

Breadcrumbs::for('finance/report/outstanding-arrears', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.outstanding_arrears'), route('finance.report.outstanding-arrears'));
});

Breadcrumbs::for('finance/report/ongoing-collection-spp', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.ongoing_collection_spp'), route('finance.report.ongoing-collection-spp'));
});

Breadcrumbs::for('finance/savings/deposit', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.deposit'), route('finance.savings.deposit'));
});

Breadcrumbs::for('finance/savings/mutation', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.mutation'), route('finance.savings.mutation'));
});

Breadcrumbs::for('finance/savings/withdrawal', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.withdrawal'), route('finance.savings.withdrawal'));
});

Breadcrumbs::for('finance/savings/withdrawal/create', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.create'), route('finance.savings.create.withdrawal'));
});

Breadcrumbs::for('finance/savings/withdrawal/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('finance/savings/withdrawal');
    $trail->push(__('label.edit'), route('finance.savings.edit.withdrawal', $id));
});

Breadcrumbs::for('finance/savings/withdrawal/history', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.history'), route('finance.savings.history.withdrawal'));
});

Breadcrumbs::for('hr/allowance', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.allowance'), route('hr.allowance.index'));
});

Breadcrumbs::for('hr/allowance/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/allowance');
    $trail->push(__('label.create'), route('hr.allowance.create'));
});

Breadcrumbs::for('hr/allowance/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('hr/allowance');
    $trail->push(__('label.edit'), route('hr.allowance.edit', $id));
});

Breadcrumbs::for('hr/employee', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.employee'), route('hr.employee.index'));
});

Breadcrumbs::for('hr/employee/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/employee');
    $trail->push(__('label.create'), route('hr.employee.create'));
});

Breadcrumbs::for('hr/employee/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('hr/employee');
    $trail->push(__('label.edit'), route('hr.employee.edit', $id));
});

Breadcrumbs::for('hr/employee/rights', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('hr/employee');
    $trail->push(__('label.access_rights'), route('hr.employee.rights', $id));
});

Breadcrumbs::for('hr/position', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.position'), route('hr.position.index'));
});

Breadcrumbs::for('hr/position/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/position');
    $trail->push(__('label.create'), route('hr.position.create'));
});

Breadcrumbs::for('hr/position/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('hr/position');
    $trail->push(__('label.edit'), route('hr.position.edit', $id));
});

Breadcrumbs::for('hr/department', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.department_head'), route('hr.department.index'));
});

Breadcrumbs::for('hr/department/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/department');
    $trail->push(__('label.create'), route('hr.department.create'));
});

Breadcrumbs::for('hr/attendance/group', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.attendance_group'), route('hr.attendance.group.index'));
});

Breadcrumbs::for('hr/attendance/group/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/attendance/group');
    $trail->push(__('label.add_attendance_group'), route('hr.attendance.group.create'));
});

Breadcrumbs::for('hr/attendance/member', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.attendance_member'), route('hr.attendance.member.index'));
});

Breadcrumbs::for('hr/attendance/member/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/attendance/member');
    $trail->push(__('label.add_attendance_member'), route('hr.attendance.member.create'));
});

Breadcrumbs::for('hr/attendance/location', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.attendance_location'), route('hr.attendance.location.index'));
});

Breadcrumbs::for('hr/attendance/location/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/attendance/location');
    $trail->push(__('label.add_attendance_location'), route('hr.attendance.location.create'));
});

Breadcrumbs::for('hr/attendance/location/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('hr/attendance/location');
    $trail->push(__('label.edit'), route('hr.attendance.location.edit', $id));
});

Breadcrumbs::for('hr/attendance/report', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.attendance_employee'), route('hr.attendance.report.index'));
});

Breadcrumbs::for('hr/employee-activity', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.employee_activity'), route('hr.employee-activity.index'));
});

Breadcrumbs::for('hr/employee-activity/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/employee-activity');
    $trail->push(__('label.create'), route('hr.employee-activity.create'));
});

Breadcrumbs::for('hr/employee-activity/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('hr/employee-activity');
    $trail->push(__('label.edit'), route('hr.employee-activity.edit', $id));
});

Breadcrumbs::for('hr/allowed-submission', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.submission_employee'), route('hr.allowed-submission.index'));
});

Breadcrumbs::for('hr/allowed-submission/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/allowed-submission');
    $trail->push(__('label.create'), route('hr.allowed-submission.create'));
});

Breadcrumbs::for('hr/inventory-item', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.inventory_item'), route('hr.inventory-item.index'));
});

Breadcrumbs::for('hr/inventory-item/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/inventory-item');
    $trail->push(__('label.create'), route('hr.inventory-item.create'));
});

Breadcrumbs::for('hr/item', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.item_data'), route('hr.item.index'));
});

Breadcrumbs::for('hr/item/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/item');
    $trail->push(__('label.create'), route('hr.item.create'));
});

Breadcrumbs::for('hr/item-category', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.item_category_master'), route('hr.item.index'));
});

Breadcrumbs::for('hr/item-category/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/item-category');
    $trail->push(__('label.create'), route('hr.item-category.create'));
});

Breadcrumbs::for('hr/location', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.location_master'), route('hr.item.index'));
});

Breadcrumbs::for('hr/location/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/location');
    $trail->push(__('label.create'), route('hr.location.create'));
});

Breadcrumbs::for('hr/location/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('hr/location');
    $trail->push(__('label.edit'), route('hr.location.edit', $id));
});

Breadcrumbs::for('hr/unit', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.unit_master'), route('hr.item.index'));
});

Breadcrumbs::for('hr/unit/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/unit');
    $trail->push(__('label.create'), route('hr.unit.create'));
});

Breadcrumbs::for('hr/unit/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('hr/unit');
    $trail->push(__('label.edit'), route('hr.unit.edit', $id));
});

Breadcrumbs::for('hr/violation', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.violation_master'), route('hr.item.index'));
});

Breadcrumbs::for('hr/violation/create', function (BreadcrumbTrail $trail) {
    $trail->parent('hr/violation');
    $trail->push(__('label.create'), route('hr.violation.create'));
});

Breadcrumbs::for('hr/violation/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('hr/violation');
    $trail->push(__('label.edit'), route('hr.violation.edit', $id));
});

Breadcrumbs::for('user', function (BreadcrumbTrail $trail, $role) {
    $trail->parent('dashboard');
    $trail->push(__('label.user'), route('user.index', $role));
});

Breadcrumbs::for('user/create', function (BreadcrumbTrail $trail, $role) {
    $trail->parent('user', $role);
    $trail->push(__('label.create'), route('user.create', $role));
});

Breadcrumbs::for('user/edit', function (BreadcrumbTrail $trail, $data) {
    $trail->parent('user', $data->role);
    $trail->push(__('label.edit'), route('user.edit', $data->id));
});

Breadcrumbs::for('setting', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.setting'), route('setting.index'));
});

Breadcrumbs::for('setting/year', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.school_year'), route('setting.year.index'));
});

Breadcrumbs::for('employee/absensi', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.absensi_tahfidz'), route('employee.tahfidz.index'));
});

Breadcrumbs::for('employee/absensi/create', function (BreadcrumbTrail $trail) {
    $trail->parent('employee/absensi');
    $trail->push(__('label.create'), route('employee.tahfidz.create'));
});

Breadcrumbs::for('employee/absensi/process', function (BreadcrumbTrail $trail) {
    $trail->parent('employee/absensi');
    $trail->push(__('label.process'), route('employee.tahfidz.create'));
});

Breadcrumbs::for('employee/student-permit', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.student_permit'), route('employee.student-permit.index'));
});

Breadcrumbs::for('employee/student-permit/create', function (BreadcrumbTrail $trail) {
    $trail->parent('employee/student-permit');
    $trail->push(__('label.create'), route('employee.student-permit.create'));
});

Breadcrumbs::for('employee/student-permit/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('employee/student-permit');
    $trail->push(__('label.edit'), route('employee.student-permit.edit', $id));
});

Breadcrumbs::for('employee/attendance', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.attendance_employee'), route('employee.attendance.index'));
});

Breadcrumbs::for('employee/hafalan', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.target_ziyadah'), route('employee.hafalan.index'));
});

Breadcrumbs::for('employee/hafalan/create', function (BreadcrumbTrail $trail) {
    $trail->parent('employee/hafalan');
    $trail->push(__('label.create'), route('employee.hafalan.create'));
});

Breadcrumbs::for('employee.hafalan.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('employee/hafalan');
    $trail->push(__('label.edit'), route('employee.hafalan.edit', $model->id));
});

Breadcrumbs::for('employee/teaching-schedule', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.teaching_schedule'), route('employee.teaching-schedule.index'));
});

Breadcrumbs::for('employee/activity-report', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.individual_activity'), route('employee.activity-report.index'));
});

Breadcrumbs::for('employee/activity-report/create', function (BreadcrumbTrail $trail) {
    $trail->parent('employee/activity-report');
    $trail->push(__('label.create'), route('employee.activity-report.create'));
});

Breadcrumbs::for('employee/activity-report/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('employee/activity-report');
    $trail->push(__('label.edit'), route('employee.activity-report.edit', $id));
});

Breadcrumbs::for('employee/committee-activity', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.committee_activity'), route('employee.committee-activity.index'));
});

Breadcrumbs::for('employee/committee-activity/create', function (BreadcrumbTrail $trail) {
    $trail->parent('employee/committee-activity');
    $trail->push(__('label.create'), route('employee.committee-activity.create'));
});

Breadcrumbs::for('employee/committee-activity/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('employee/committee-activity');
    $trail->push(__('label.edit'), route('employee.committee-activity.edit', $id));
});

Breadcrumbs::for('employee/submission', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.submission_item'), route('employee.submission.index'));
});

Breadcrumbs::for('employee/submission/create', function (BreadcrumbTrail $trail) {
    $trail->parent('employee/submission');
    $trail->push(__('label.create'), route('employee.submission.create'));
});

Breadcrumbs::for('employee/submission/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('employee/submission');
    $trail->push(__('label.edit'), route('employee.submission.edit', $id));
});

Breadcrumbs::for('employee/submission/item', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.submission_item'), route('employee.submission.create'));
});

Breadcrumbs::for('employee/submission/item/create', function (BreadcrumbTrail $trail) {
    $trail->parent('employee/submission/item');
    $trail->push(__('label.create'), route('employee.submission.item.create'));
});

Breadcrumbs::for('employee/submission/item/edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('employee/submission/item');
    $trail->push(__('label.edit'), route('employee.submission.item.edit', $id));
});

Breadcrumbs::for('employee/lunch-report', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.lunch_report'), route('employee.lunch-report.index'));
});

Breadcrumbs::for('employee/permit', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('label.employee_permit'), route('employee.permit.index'));
});

Breadcrumbs::for('employee/permit/create', function (BreadcrumbTrail $trail) {
    $trail->parent('employee/permit');
    $trail->push(__('label.create'), route('employee.permit.create'));
});
