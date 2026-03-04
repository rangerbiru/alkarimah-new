@php
$set_finance_withdrawal = ($action == 'savings' && $function == 'withdrawal') ? ' active' : '';
$set_finance_history = ($action == 'savings' && $function == 'history') ? ' active' : '';
@endphp

<li class="slide__category"><span class="category-name">{{ __('label.savings') }}</span></li>

<li class="slide">
    <a href="{{ route('finance.savings.create.withdrawal') }}" class="side-menu__item{{ $set_finance_withdrawal }}">
        <span class=" side-menu__icon">
            <i class="bx bx-wallet"></i>
        </span>
        <span class="side-menu__label">Pengambilan</span>
    </a>
</li>
<li class="slide">
    <a href="{{ route('finance.savings.history.withdrawal') }}" class="side-menu__item{{ $set_finance_history }}">
        <span class=" side-menu__icon">
            <i class="bx bx-clipboard"></i>
        </span>
        <span class="side-menu__label">Riwayat</span>
    </a>
</li>
