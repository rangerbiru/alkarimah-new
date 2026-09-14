@php
    $set_finance_report_billpertype = $action == 'report' && $function == 'bill-per-type' ? ' active' : '';

    if ($action == 'report') {
        $set_finance_report = ' active';
        $set_finance_report_open = ' open';
    } else {
        $set_finance_report = '';
        $set_finance_report_open = '';
    }
@endphp

<li class="slide__category"><span class="category-name">FINANCE</span></li>

<li class="slide has-sub{{ $set_finance_report_open }}">
    <a href="javascript:void(0);" class="side-menu__item{{ $set_finance_report }}">
        <span class="side-menu__icon">
            <i class="bx bx-file"></i>
        </span>
        <span class="side-menu__label">{{ __('label.report') }}</span>
        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('finance.report.bill-per-type') }}"
                class="side-menu__item{{ $set_finance_report_billpertype }}">
                {{ __('label.bill_per_type') }}
            </a>
        </li>
    </ul>
</li>
