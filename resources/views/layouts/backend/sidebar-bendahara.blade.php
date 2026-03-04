@php
    $set_finance_transaction_cash = $action == 'transaction' && $function == 'cash' ? ' active' : '';
    $set_finance_transaction_unique_code = $action == 'transaction' && $function == 'unique-code' ? ' active' : '';
    $set_finance_report_billstudent = $action == 'report' && $function == 'bill-student' ? ' active' : '';
    $set_payroll_allowance = $action == 'hr' && $function == 'allowance' ? ' active' : '';
    $set_payroll_setup = $action == 'payroll' && $function == 'setup' ? ' active' : '';
    $set_payroll_slip = $action == 'payroll' && $function == 'slip' ? ' active' : '';
    $set_submission = $action == 'submission' && $function == 'index' ? ' active' : '';

    if ($action == 'transaction') {
        $set_finance_transaction = ' active';
        $set_finance_transaction_open = ' open';
    } else {
        $set_finance_transaction = '';
        $set_finance_transaction_open = '';
    }

    if ($action == 'report') {
        $set_finance_report = ' active';
        $set_finance_report_open = ' open';
    } else {
        $set_finance_report = '';
        $set_finance_report_open = '';
    }
@endphp

<li class="slide__category"><span class="category-name">{{ __('label.finance') }}</span></li>

<li class="slide has-sub{{ $set_finance_transaction_open }}">
    <a href="javascript:void(0);" class="side-menu__item{{ $set_finance_transaction }}">
        <span class="side-menu__icon">
            <i class="bx bx-receipt"></i>
        </span>
        <span class="side-menu__label">Transaksi</span>

        @if ($data->transaction_deposit > 0)
            @php
                $sbc = $data->transaction_deposit > 99 ? '+' . $data->transaction_deposit : $data->transaction_deposit;
            @endphp
            <span class="badge bg-danger-transparent ms-2 d-inline-block">{{ $sbc }}</span>
        @endif

        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('finance.transaction.cash', 'waiting') }}"
                class="side-menu__item{{ $set_finance_transaction_cash }}">
                Setoran Kas

                @if ($data->transaction_cash > 0)
                    @php
                        $sbc = $data->transaction_cash > 99 ? '+' . $data->transaction_cash : $data->transaction_cash;
                    @endphp
                    <span class="badge bg-danger-transparent ms-2 d-inline-block">{{ $sbc }}</span>
                @endif
            </a>
        </li>
        {{-- <li class="slide">
            <a href="{{ route('finance.transaction.unique-code', 'waiting') }}" class="side-menu__item{{ $set_finance_transaction_unique_code }}">
                Setoran Kode Unik

                @if ($data->transaction_unique_code > 0)
                    @php
                    $sbc = ($data->transaction_unique_code > 99) ? '+' . $data->transaction_unique_code : $data->transaction_unique_code;
                    @endphp
                    <span class="badge bg-danger-transparent ms-2 d-inline-block">{{ $sbc }}</span>
                @endif
            </a>
        </li> --}}
    </ul>
</li>
<li class="slide has-sub{{ $set_finance_report_open }}">
    <a href="javascript:void(0);" class="side-menu__item{{ $set_finance_report }}">
        <span class="side-menu__icon">
            <i class="bx bx-file"></i>
        </span>
        <span class="side-menu__label">Laporan</span>
        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('finance.report.bill-student') }}"
                class="side-menu__item{{ $set_finance_report_billstudent }}">
                Tagihan Per Siswa
            </a>
        </li>
    </ul>
</li>

<li class="slide__category pt-3"><span class="category-name">PAYROLL</span></li>
<li class="slide">
    <a href="{{ route('hr.allowance.index') }}" class="side-menu__item{{ $set_payroll_allowance }}">
        <span class=" side-menu__icon">
            <i class="bx bx-credit-card-alt"></i>
        </span>
        <span class="side-menu__label">{{ __('label.allowance') }}</span>
    </a>
</li>
<li class="slide">
    <a href="{{ route('finance.payroll.setup') }}" class="side-menu__item{{ $set_payroll_setup }}">
        <span class=" side-menu__icon">
            <i class="ti ti-device-ipad-dollar"></i>
        </span>
        <span class="side-menu__label">{{ __('label.setup') }}</span>
    </a>
</li>
<li class="slide">
    <a href="{{ route('finance.payroll.slip') }}" class="side-menu__item{{ $set_payroll_slip }}">
        <span class=" side-menu__icon">
            <i class="ti ti-receipt-dollar"></i>
        </span>
        <span class="side-menu__label">{{ __('label.salary_slip') }}</span>
    </a>
</li>
