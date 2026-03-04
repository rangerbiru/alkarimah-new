@php
    $set_user_bendahara = $controller == 'user' && request()->render == 'bendahara' ? ' active' : '';
    $set_user_kasir = $controller == 'user' && request()->render == 'kasir' ? ' active' : '';
    $set_user_walikelas = $controller == 'user' && request()->render == 'wali-kelas' ? ' active' : '';
    $set_user_penanngungjawab =
        $controller == 'user' && request()->render == 'penanggung-jawab-tabungan' ? ' active' : '';
    $set_academic_asrama = $controller == 'academic' && $action == 'asrama' ? ' active' : '';
    $set_academic_class = $controller == 'academic' && $action == 'class' ? ' active' : '';
    $set_academic_class_hours = $controller == 'academic' && $action == 'class-hours' ? ' active' : '';
    $set_academic_class_schedule = $controller == 'academic' && $action == 'class-schedule' ? ' active' : '';
    $set_academic_halaqah = $controller == 'academic' && $action == 'halaqah' ? ' active' : '';
    $set_academic_parent = $controller == 'academic' && $action == 'parent' ? ' active' : '';
    $set_academic_student = $controller == 'academic' && $action == 'student' ? ' active' : '';
    $set_academic_subject = $controller == 'academic' && $action == 'subject' ? ' active' : '';
    $set_academic_excul = $controller == 'academic' && $action == 'excul' ? ' active' : '';
    $set_academic_basic = $controller == 'academic' && $action == 'basic' ? ' active' : '';
    $set_academic_absence_type =
        $controller == 'academic' && $action == 'absence' && $function == 'type' ? ' active' : '';
    $set_academic_absence_report =
        $controller == 'academic' && $action == 'absence' && $function == 'report' ? ' active' : '';
    //add student permission
    $set_academic_student_permit =
        $controller == 'academic' && $action == 'absence' && $function == 'permission' ? ' active' : '';
    //add data permit
    $set_academic_permit =
        $controller == 'academic' && $action == 'absence' && $function == 'data-permit' ? ' active' : '';
    $set_hr_employee = $controller == 'hr' && $action == 'employee' ? ' active' : '';
    $set_hr_permit_type = $controller == 'hr' && $action == 'permit-type' ? ' active' : '';
    $set_hr_position = $controller == 'hr' && $action == 'position' ? ' active' : '';
    $set_hr_department = $controller == 'hr' && $action == 'department' ? ' active' : '';
    $set_hr_employee_activity =
        $controller == 'hr' && $action == 'employee' && $function == 'employee-activity' ? ' active' : '';

    $set_hr_allowed_submission =
        $controller == 'hr' && $action == 'employee' && $function == 'allowed-submission' ? ' active' : '';

    $set_hr_inventory_item =
        $controller == 'hr' && $action == 'employee' && $function == 'inventory-item' ? ' active' : '';

    $set_hr_item = $controller == 'hr' && $action == 'employee' && $function == 'item' ? ' active' : '';
    $set_hr_item_category =
        $controller == 'hr' && $action == 'employee' && $function == 'item-category' ? ' active' : '';
    $set_hr_location_master = $controller == 'hr' && $action == 'employee' && $function == 'location' ? ' active' : '';
    $set_hr_unit_master = $controller == 'hr' && $action == 'employee' && $function == 'unit' ? ' active' : '';
    $set_hr_violation_master =
        $controller == 'hr' && $action == 'employee' && $function == 'violation' ? ' active' : '';

    $set_hr_attendance_report =
        $controller == 'hr' && $action == 'attendance' && $function == 'report' ? ' active' : '';
    $set_hr_attendance_group = $controller == 'hr' && $action == 'attendance' && $function == 'group' ? ' active' : '';
    $set_hr_attendance_member =
        $controller == 'hr' && $action == 'attendance' && $function == 'member' ? ' active' : '';

    $set_hr_attendance_location =
        $controller == 'hr' && $action == 'attendance' && $function == 'location' ? ' active' : '';
@endphp

<li class="slide has-sub">
    <a href="javascript:void(0);" class="side-menu__item">
        <span class="side-menu__icon">
            <i class="bx bx-user"></i>
        </span>
        <span class="side-menu__label">{{ __('label.user') }}</span>
        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('user.index', 'bendahara') }}" class="side-menu__item{{ $set_user_bendahara }}">
                {{ __('label.bendahara') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('user.index', 'kasir') }}" class="side-menu__item{{ $set_user_kasir }}">
                {{ __('label.kasir') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('user.index', 'wali-kelas') }}" class="side-menu__item{{ $set_user_walikelas }}">
                {{ __('label.wali_kelas') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('user.index', 'penanggung-jawab-tabungan') }}"
                class="side-menu__item{{ $set_user_penanngungjawab }}">
                {{ __('label.penanggung_jawab_tabungan') }}
            </a>
        </li>
    </ul>
</li>

<li class="slide__category"><span class="category-name">{{ __('label.academic') }}</span></li>

<li class="slide">
    <a href="{{ route('academic.basic.index') }}" class="side-menu__item{{ $set_academic_basic }}">
        <span class=" side-menu__icon">
            <i class='bx bx-book-content'></i>
        </span>
        <span class="side-menu__label">{{ __('label.basic_data') }}</span>
    </a>
</li>

<li class="slide">
    <a href="{{ route('academic.asrama.index') }}" class="side-menu__item{{ $set_academic_asrama }}">
        <span class=" side-menu__icon">
            <i class="bx bx-building-house"></i>
        </span>
        <span class="side-menu__label">{{ __('label.asrama') }}</span>
    </a>
</li>
<li class="slide">
    <a href="{{ route('academic.halaqah.index') }}" class="side-menu__item{{ $set_academic_halaqah }}">
        <span class=" side-menu__icon">
            <i class="bx bx-building-house"></i>
        </span>
        <span class="side-menu__label">{{ __('label.halaqah') }}</span>
    </a>
</li>
<li class="slide has-sub">
    <a href="javascript:void(0);" class="side-menu__item">
        <span class="side-menu__icon">
            <i class="bx bx-buildings"></i>
        </span>
        <span class="side-menu__label">{{ __('label.class') }}</span>
        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('academic.class.index') }}" class="side-menu__item{{ $set_academic_class }}">
                {{ __('label.set_class') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('academic.class-hours.index') }}"
                class="side-menu__item{{ $set_academic_class_hours }}">
                {{ __('label.manage_class_hours') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('academic.class-schedule.index') }}"
                class="side-menu__item{{ $set_academic_class_schedule }}">
                {{ __('label.manage_lesson_schedule') }}
            </a>
        </li>
    </ul>
</li>
<li class="slide">
    <a href="{{ route('academic.subject.index') }}" class="side-menu__item{{ $set_academic_subject }}">
        <span class=" side-menu__icon">
            <i class="bx bx-book-content"></i>
        </span>
        <span class="side-menu__label">{{ __('label.subject') }}</span>
    </a>
</li>
<li class="slide">
    <a href="{{ route('academic.parent.index') }}" class="side-menu__item{{ $set_academic_parent }}">
        <span class=" side-menu__icon">
            <i class="bx bx-user"></i>
        </span>
        <span class="side-menu__label">{{ __('label.parent') }}</span>
    </a>
</li>
<li class="slide">
    <a href="{{ route('academic.student.index') }}" class="side-menu__item{{ $set_academic_student }}">
        <span class=" side-menu__icon">
            <i class="bx bx-user"></i>
        </span>
        <span class="side-menu__label">{{ __('label.student') }}</span>
    </a>
</li>


<li class="slide">
    <a href="{{ route('academic.excul.index') }}" class="side-menu__item{{ $set_academic_excul }}">
        <span class=" side-menu__icon">
            <i class="bx bx-universal-access"></i>
        </span>
        <span class="side-menu__label">{{ __('label.excul') }}</span>
    </a>
</li>

<li class="slide has-sub">
    <a href="javascript:void(0);" class="side-menu__item">
        <span class="side-menu__icon">
            <i class="bx bx-fingerprint"></i>
        </span>
        <span class="side-menu__label">{{ __('label.absence') }}</span>
        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('academic.absence.type.index') }}"
                class="side-menu__item{{ $set_academic_absence_type }}">
                {{ __('label.type') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('academic.absence.report') }}"
                class="side-menu__item{{ $set_academic_absence_report }}">
                {{ __('label.report') }}
            </a>
        </li>
    </ul>
</li>

<li class="slide has-sub">
    <a href="javascript:void(0);" class="side-menu__item">
        <span class="side-menu__icon">
            <i class="bx bx-user"></i>
        </span>
        <span class="side-menu__label">{{ __('label.student_permit') }}</span>
        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('academic.student-permit-group.index') }}"
                class="side-menu__item{{ $set_academic_permit }}">
                {{ __('label.permit_data') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('academic.student-permit.index') }}"
                class="side-menu__item{{ $set_academic_student_permit }}">
                <span class="side-menu__label">{{ __('label.student_permit') }}</span>
            </a>
        </li>
    </ul>
</li>

<li class="slide">
    <a href="{{ route('hr.violation.index') }}" class="side-menu__item{{ $set_hr_violation_master }}">
        <span class=" side-menu__icon">
            <i class="bx bx-error"></i>
        </span>
        <span class="side-menu__label">{{ __('label.violation_master') }}</span>
    </a>
</li>



<li class="slide__category"><span class="category-name">{{ __('label.hrd') }}</span></li>

<li class="slide has-sub">
    <a href="javascript:void(0);" class="side-menu__item">
        <span class="side-menu__icon">
            <i class="bx bx-user"></i>
        </span>
        <span class="side-menu__label">{{ __('label.employee') }}</span>
        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('hr.employee.index') }}" class="side-menu__item{{ $set_hr_employee }}">
                {{ __('label.employee') }}
            </a>
        </li>
        {{--  --}}
        <li class="slide">
            <a href="{{ route('hr.permit-type.index') }}" class="side-menu__item{{ $set_hr_permit_type }}">
                {{ __('label.manage_permit_type') }}
            </a>
        </li>
    </ul>
</li>

<li class="slide has-sub">
    <a href="javascript:void(0);" class="side-menu__item">
        <span class="side-menu__icon">
            <i class="bx bx-fingerprint"></i>
        </span>
        <span class="side-menu__label">{{ __('label.attendance_employee') }}</span>
        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('hr.attendance.member.index') }}"
                class="side-menu__item{{ $set_hr_attendance_member }}">
                {{ __('label.attendance_member') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('hr.attendance.group.index') }}"
                class="side-menu__item{{ $set_hr_attendance_group }}">
                {{ __('label.attendance_group') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('hr.attendance.location.index') }}"
                class="side-menu__item{{ $set_hr_attendance_location }}">
                {{ __('label.attendance_location') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('hr.attendance.report.index') }}"
                class="side-menu__item{{ $set_hr_attendance_report }}">
                {{ __('label.report') }}
            </a>
        </li>
    </ul>
</li>

<li class="slide has-sub">
    <a href="javascript:void(0);" class="side-menu__item">
        <span class="side-menu__icon">
            <i class="ti ti-briefcase"></i>
        </span>
        <span class="side-menu__label">{{ __('label.position') }}</span>
        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('hr.position.index') }}" class="side-menu__item{{ $set_hr_position }}">
                {{ __('label.position') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('hr.department.index') }}" class="side-menu__item{{ $set_hr_attendance_location }}">
                {{ __('label.department_head') }}
            </a>
        </li>
    </ul>
</li>

<li class="slide">
    <a href="{{ route('hr.employee-activity.index') }}" class="side-menu__item{{ $set_hr_employee_activity }}">
        <span class=" side-menu__icon">
            <i class="bx bxs-user-rectangle"></i>
        </span>
        <span class="side-menu__label">{{ __('label.employee_activity') }}</span>
    </a>
</li>

{{-- <li class="slide">
    <a href="{{ route('hr.allowed-submission.index') }}" class="side-menu__item{{ $set_hr_employee_activity }}">
        <span class=" side-menu__icon">
            <i class="bx bxs-package"></i>
        </span>
        <span class="side-menu__label">{{ __('label.submission_employee') }}</span>
    </a>
</li> --}}


<li class="slide has-sub">
    <a href="javascript:void(0);" class="side-menu__item">
        <span class="side-menu__icon">
            <i class="bx bxs-package"></i>
        </span>
        <span class="side-menu__label">{{ __('label.submission') }}</span>
        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('hr.allowed-submission.index') }}"
                class="side-menu__item{{ $set_hr_allowed_submission }}">
                {{ __('label.submission_employee') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('hr.inventory-item.index') }}" class="side-menu__item{{ $set_hr_inventory_item }}">
                {{ __('label.inventory_item') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('hr.item.index') }}" class="side-menu__item{{ $set_hr_item }}">
                {{ __('label.item_data') }}
            </a>
        </li>

        {{-- <li class="slide">
            <a href="{{ route('hr.item.index') }}" class="side-menu__item{{ $set_hr_item }}">
                {{ __('label.item_data') }}
            </a>
        </li>

        <li class="slide">
            <a href="{{ route('hr.item.index') }}" class="side-menu__item{{ $set_hr_item }}">
                {{ __('label.item_data') }}
            </a>
        </li> --}}
    </ul>
</li>

<li class="slide">
    <a href="{{ route('hr.item-category.index') }}" class="side-menu__item{{ $set_hr_item_category }}">
        <span class=" side-menu__icon">
            <i class="bx bxs-category"></i>
        </span>
        <span class="side-menu__label">{{ __('label.item_category_master') }}</span>
    </a>
</li>

<li class="slide">
    <a href="{{ route('hr.location.index') }}" class="side-menu__item{{ $set_hr_location_master }}">
        <span class=" side-menu__icon">
            <i class="bx bx-current-location"></i>
        </span>
        <span class="side-menu__label">{{ __('label.location_master') }}</span>
    </a>
</li>

<li class="slide">
    <a href="{{ route('hr.unit.index') }}" class="side-menu__item{{ $set_hr_unit_master }}">
        <span class=" side-menu__icon">
            <i class="bx bx-buildings"></i>
        </span>
        <span class="side-menu__label">{{ __('label.unit_master') }}</span>
    </a>
</li>
