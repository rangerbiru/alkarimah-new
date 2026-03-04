@php
$set_finance_bill_type = ($action == 'bill' && $function == 'type') ? ' active' : '';
$set_finance_bill_setup = ($action == 'bill' && ($function == 'setup' or $function == 'index')) ? ' active' : '';
$set_finance_bill_discount = ($action == 'bill' && $function == 'discount') ? ' active' : '';
$set_finance_transaction_bill = ($action == 'transaction' && $function == 'bill') ? ' active' : '';
$set_finance_transaction_cash = ($action == 'transaction' && $function == 'cash') ? ' active' : '';
$set_finance_transaction_unique_code = ($action == 'transaction' && $function == 'unique-code') ? ' active' : '';
$set_finance_transaction_pending = ($action == 'transaction' && $function == 'pending') ? ' active' : '';
$set_finance_transaction_history = ($action == 'transaction' && $function == 'history') ? ' active' : '';
$set_finance_savings_deposit = ($action == 'savings' && $function == 'deposit') ? ' active' : '';
$set_finance_savings_withdrawal = ($action == 'savings' && $function == 'withdrawal') ? ' active' : '';
$set_finance_savings_mutation = ($action == 'savings' && $function == 'mutation') ? ' active' : '';
$set_finance_donation = ($action == 'donation') ? ' active' : '';
$set_finance_report_billnotpaid = ($action == 'report' && $function == 'bill-not-paid') ? ' active' : '';
$set_finance_report_billstudent = ($action == 'report' && $function == 'bill-student') ? ' active' : '';
$set_finance_report_billprogress = ($action == 'report' && $function == 'bill-progress') ? ' active' : '';
$set_finance_report_billtotal = ($action == 'report' && $function == 'bill-total') ? ' active' : '';
$set_finance_report_paymentmethod = ($action == 'report' && $function == 'payment-method') ? ' active' : '';
$set_finance_report_outstandingarrears = ($action == 'report' && $function == 'outstanding-arrears') ? ' active' : '';
$set_finance_report_donation = ($action == 'report' && $function == 'donation') ? ' active' : '';
$set_finance_report_ongoingcollectionspp = ($action == 'report' && $function == 'ongoing-collection-spp') ? ' active' : '';

if ($action == 'bill') {
    $set_finance_bill = ' active';
    $set_finance_bill_open = ' open';
} else {
    $set_finance_bill = '';
    $set_finance_bill_open = '';
}

if ($action == 'transaction') {
    $set_finance_transaction = ' active';
    $set_finance_transaction_open = ' open';
} else {
    $set_finance_transaction = '';
    $set_finance_transaction_open = '';
}

if ($action == 'savings') {
    $set_finance_savings = ' active';
    $set_finance_savings_open = ' open';
} else {
    $set_finance_savings = '';
    $set_finance_savings_open = '';
}

if ($action == 'report') {
    $set_finance_report = ' active';
    $set_finance_report_open = ' open';
} else {
    $set_finance_report = '';
    $set_finance_report_open = '';
}
@endphp

<li class="slide__category"><span class="category-name">FINANCE</span></li>

<li class="slide has-sub{{ $set_finance_bill_open }}">
    <a href="javascript:void(0);" class="side-menu__item{{ $set_finance_bill }}">
        <span class="side-menu__icon">
            <i class="bx bx-credit-card-front"></i>
        </span>
        <span class="side-menu__label">{{ __('label.bill') }}</span>
        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('finance.bill.type.index') }}" class="side-menu__item{{ $set_finance_bill_type }}">
                {{ __('label.type') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.bill.setup.index') }}" class="side-menu__item{{ $set_finance_bill_setup }}">
                {{ __('label.setup') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.bill.discount.index') }}" class="side-menu__item{{ $set_finance_bill_discount }}">
                {{ __('label.discount') }}
            </a>
        </li>
    </ul>
</li>
<li class="slide has-sub{{ $set_finance_transaction_open }}">
    <a href="javascript:void(0);" class="side-menu__item{{ $set_finance_transaction }}">
        <span class="side-menu__icon">
            <i class="bx bx-receipt"></i>
        </span>
        <span class="side-menu__label">{{ __('label.transaction') }}</span>

        @if ($data->transaction_pending > 0)
            @php
            $sbc = ($data->transaction_pending > 99) ? '+' . $data->transaction_pending : $data->transaction_pending;
            @endphp
            <span class="badge bg-danger-transparent ms-2 d-inline-block">{{ $sbc }}</span>
        @endif

        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('finance.transaction.bill.index') }}" class="side-menu__item{{ $set_finance_transaction_bill }}">
                {{ __('label.payment') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.transaction.pending') }}" class="side-menu__item{{ $set_finance_transaction_pending }}">
                {{ __('label.pending') }}

                @if ($data->transaction_pending > 0)
                    @php
                    $sbc = ($data->transaction_pending > 99) ? '+' . $data->transaction_pending : $data->transaction_pending;
                    @endphp
                    <span class="badge bg-danger-transparent ms-2 d-inline-block">{{ $sbc }}</span>
                @endif
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.transaction.cash', 'waiting') }}" class="side-menu__item{{ $set_finance_transaction_cash }}">
                {{ __('label.cash_deposit') }}
            </a>
        </li>
        {{-- <li class="slide">
            <a href="{{ route('finance.transaction.unique-code', 'waiting') }}" class="side-menu__item{{ $set_finance_transaction_unique_code }}">
                Setoran Kode Unik
            </a>
        </li> --}}
        <li class="slide">
            <a href="{{ route('finance.transaction.history') }}" class="side-menu__item{{ $set_finance_transaction_history }}">
                {{ __('label.history') }}
            </a>
        </li>
    </ul>
</li>
<li class="slide has-sub{{ $set_finance_savings_open }}">
    <a href="javascript:void(0);" class="side-menu__item{{ $set_finance_savings }}">
        <span class="side-menu__icon">
            <i class="bx bx-wallet"></i>
        </span>
        <span class="side-menu__label">{{ __('label.savings') }}</span>

        @if ($data->savings_withdrawal > 0)
            @php
            $sbc = ($data->savings_withdrawal > 99) ? '+' . $data->savings_withdrawal : $data->savings_withdrawal;
            @endphp
            <span class="badge bg-danger-transparent ms-2 d-inline-block">{{ $sbc }}</span>
        @endif

        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('finance.savings.deposit') }}" class="side-menu__item{{ $set_finance_savings_deposit }}">
                {{ __('label.deposit') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.savings.withdrawal') }}" class="side-menu__item{{ $set_finance_savings_withdrawal }}">
                {{ __('label.withdrawal') }}

                @if ($data->savings_withdrawal > 0)
                    @php
                    $sbc = ($data->savings_withdrawal > 99) ? '+' . $data->savings_withdrawal : $data->savings_withdrawal;
                    @endphp
                    <span class="badge bg-danger-transparent ms-2 d-inline-block">{{ $sbc }}</span>
                @endif
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.savings.mutation') }}" class="side-menu__item{{ $set_finance_savings_mutation }}">
                {{ __('label.mutation') }}
            </a>
        </li>
    </ul>
</li>
<li class="slide">
    <a href="{{ route('finance.donation.index') }}" class="side-menu__item{{ $set_finance_donation }}">
        <span class=" side-menu__icon">
            <i class="bx bx-donate-heart"></i>
        </span>
        <span class="side-menu__label">{{ __('label.donation') }}</span>
    </a>
</li>
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
            <a href="{{ route('finance.report.bill-not-paid') }}" class="side-menu__item{{ $set_finance_report_billnotpaid }}">
                {{ __('label.bill_not_paid') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.report.bill-student') }}" class="side-menu__item{{ $set_finance_report_billstudent }}">
                {{ __('label.bill_per_student') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.report.bill-progress') }}" class="side-menu__item{{ $set_finance_report_billprogress }}">
                {{ __('label.bill_progress') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.report.bill-total') }}" class="side-menu__item{{ $set_finance_report_billtotal }}">
                {{ __('label.bill_total') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.report.payment-method') }}" class="side-menu__item{{ $set_finance_report_paymentmethod }}">
                {{ __('label.payment_method') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.report.outstanding-arrears') }}" class="side-menu__item{{ $set_finance_report_outstandingarrears }}">
                {{ __('label.outstanding_arrears') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.report.ongoing-collection-spp') }}" class="side-menu__item{{ $set_finance_report_ongoingcollectionspp }}">
                {{ __('label.ongoing_collection_spp') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.report.donation') }}" class="side-menu__item{{ $set_finance_report_donation }}">
                {{ __('label.donation') }}
            </a>
        </li>
    </ul>
</li>
