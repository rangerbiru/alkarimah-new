@php
    $set_finance_transaction_bill = $action == 'transaction' && $function == 'bill' ? ' active' : '';
    $set_finance_transaction_cash = $action == 'transaction' && $function == 'cash' ? ' active' : '';
    $set_finance_transaction_unique_code = $action == 'transaction' && $function == 'unique-code' ? ' active' : '';
    $set_finance_transaction_pending = $action == 'transaction' && $function == 'pending' ? ' active' : '';
    $set_finance_transaction_history = $action == 'transaction' && $function == 'history' ? ' active' : '';
    $set_finance_balance_topup = $action == 'balance' && $function == 'topup' ? ' active' : '';
    $set_finance_balance_withdrawal = $action == 'balance' && $function == 'withdrawal' ? ' active' : '';
    $set_finance_savings_deposit = $action == 'savings' && $function == 'deposit' ? ' active' : '';
    $set_finance_savings_withdrawal = $action == 'savings' && $function == 'withdrawal' ? ' active' : '';
    $set_finance_savings_mutation = $action == 'savings' && $function == 'mutation' ? ' active' : '';

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

    if ($action == 'balance') {
        $set_finance_balance = ' active';
        $set_finance_balance_open = ' open';
    } else {
        $set_finance_balance = '';
        $set_finance_balance_open = '';
    }
@endphp

<li class="slide__category"><span class="category-name">FINANCE</span></li>

<li class="slide has-sub{{ $set_finance_transaction_open }}">
    <a href="javascript:void(0);" class="side-menu__item{{ $set_finance_transaction }}">
        <span class="side-menu__icon">
            <i class="bx bx-receipt"></i>
        </span>
        <span class="side-menu__label">{{ __('label.transaction') }}</span>

        @if ($data->transaction_pending > 0)
            @php
                $sbc = $data->transaction_pending > 99 ? '+' . $data->transaction_pending : $data->transaction_pending;
            @endphp
            <span class="badge bg-danger-transparent ms-2 d-inline-block">{{ $sbc }}</span>
        @endif

        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('finance.transaction.bill.index') }}"
                class="side-menu__item{{ $set_finance_transaction_bill }}">
                {{ __('label.payment') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.transaction.pending') }}"
                class="side-menu__item{{ $set_finance_transaction_pending }}">
                {{ __('label.pending') }}

                @if ($data->transaction_pending > 0)
                    @php
                        $sbc =
                            $data->transaction_pending > 99
                                ? '+' . $data->transaction_pending
                                : $data->transaction_pending;
                    @endphp
                    <span class="badge bg-danger-transparent ms-2 d-inline-block">{{ $sbc }}</span>
                @endif
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.transaction.cash', 'waiting') }}"
                class="side-menu__item{{ $set_finance_transaction_cash }}">
                {{ __('label.cash_deposit') }}
            </a>
        </li>
        {{-- <li class="slide">
            <a href="{{ route('finance.transaction.unique-code', 'waiting') }}" class="side-menu__item{{ $set_finance_transaction_unique_code }}">
                Setoran Kode Unik
            </a>
        </li> --}}
        <li class="slide">
            <a href="{{ route('finance.transaction.history') }}"
                class="side-menu__item{{ $set_finance_transaction_history }}">
                {{ __('label.history') }}
            </a>
        </li>
    </ul>
</li>
<li class="slide has-sub{{ $set_finance_balance_open }}">
    <a href="javascript:void(0);" class="side-menu__item{{ $set_finance_balance }}">
        <span class="side-menu__icon">
            <i class="bx bx-wallet"></i>
        </span>
        <span class="side-menu__label">{{ __('label.topup') }}</span>
        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('finance.balance.topup') }}" class="side-menu__item{{ $set_finance_balance_topup }}">
                {{ __('label.deposit') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.balance.withdrawal') }}" class="side-menu__item{{ $set_finance_balance_withdrawal }}">
                {{ __('label.withdrawal') }}
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
                $sbc = $data->savings_withdrawal > 99 ? '+' . $data->savings_withdrawal : $data->savings_withdrawal;
            @endphp
            <span class="badge bg-danger-transparent ms-2 d-inline-block">{{ $sbc }}</span>
        @endif

        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('finance.savings.deposit') }}"
                class="side-menu__item{{ $set_finance_savings_deposit }}">
                {{ __('label.deposit') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.savings.withdrawal') }}"
                class="side-menu__item{{ $set_finance_savings_withdrawal }}">
                {{ __('label.withdrawal') }}

                @if ($data->savings_withdrawal > 0)
                    @php
                        $sbc =
                            $data->savings_withdrawal > 99
                                ? '+' . $data->savings_withdrawal
                                : $data->savings_withdrawal;
                    @endphp
                    <span class="badge bg-danger-transparent ms-2 d-inline-block">{{ $sbc }}</span>
                @endif
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('finance.savings.mutation') }}"
                class="side-menu__item{{ $set_finance_savings_mutation }}">
                {{ __('label.mutation') }}
            </a>
        </li>
    </ul>
</li>
