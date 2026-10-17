{{-- Parent menu for a dual-role user (employee + parent). Single-role parents keep their own navigation --}}
@php
    $set_parent_payment = ($controller == 'finance' && $action == 'payment') ? ' active' : '';
    $set_parent_balance = ($controller == 'finance' && $action == 'balance') ? ' active' : '';
    $set_parent_savings = ($controller == 'finance' && $action == 'savings') ? ' active' : '';
    $set_parent_children = ($controller == 'academic' && $action == 'student' && $function == 'children') ? ' active' : '';
@endphp

<li class="slide__category"><span class="category-name">{{ __('label.parent_menu') }}</span></li>

<li class="slide">
    <a href="{{ route('academic.student.children') }}" class="side-menu__item{{ $set_parent_children }}">
        <span class="side-menu__icon">
            <i class="bx bx-child"></i>
        </span>
        <span class="side-menu__label">{{ __('label.my_children') }}</span>
    </a>
</li>
<li class="slide">
    <a href="{{ route('finance.payment.index') }}" class="side-menu__item{{ $set_parent_payment }}">
        <span class="side-menu__icon">
            <i class="bx bx-credit-card"></i>
        </span>
        <span class="side-menu__label">{{ __('label.payment') }}</span>
    </a>
</li>
<li class="slide">
    <a href="{{ route('finance.balance.index') }}" class="side-menu__item{{ $set_parent_balance }}">
        <span class="side-menu__icon">
            <i class="bx bx-wallet"></i>
        </span>
        <span class="side-menu__label">{{ __('label.topup_balance') }}</span>
    </a>
</li>
<li class="slide">
    <a href="{{ route('finance.savings.index') }}" class="side-menu__item{{ $set_parent_savings }}">
        <span class="side-menu__icon">
            <i class="bx bx-money"></i>
        </span>
        <span class="side-menu__label">{{ __('label.savings') }}</span>
    </a>
</li>
