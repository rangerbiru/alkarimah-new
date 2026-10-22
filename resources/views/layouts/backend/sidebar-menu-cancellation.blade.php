@php
    $set_cancellation = $controller == 'finance' && $action == 'cancellation';
    $set_cancellation_index = $set_cancellation && $function == 'index' ? ' active' : '';
    $set_cancellation_history = $set_cancellation && $function == 'history' ? ' active' : '';
@endphp

<li class="slide__category"><span class="category-name">{{ __('label.finance') }}</span></li>

<li class="slide">
    <a href="{{ route('finance.cancellation.index') }}" class="side-menu__item{{ $set_cancellation_index }}">
        <span class="side-menu__icon">
            <i class="bx bx-revision"></i>
        </span>
        <span class="side-menu__label">{{ __('label.transaction_cancellation') }}</span>
    </a>
</li>
<li class="slide">
    <a href="{{ route('finance.cancellation.history') }}" class="side-menu__item{{ $set_cancellation_history }}">
        <span class="side-menu__icon">
            <i class="bx bx-history"></i>
        </span>
        <span class="side-menu__label">{{ __('label.cancellation_history') }}</span>
    </a>
</li>
