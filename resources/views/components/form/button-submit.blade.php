<div class="mt-4">
    <button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-primary btn-submit']) }}
        data-loading="{{ $loading . '...' }}">
        @if ($iconPosition == 'right')
            {{ $label }} &nbsp;<i class="{{ $icon }}"></i>
        @else
            <i class="{{ $icon }}"></i> &nbsp;{{ $label }}
        @endif
    </button>

    @if (!empty($cancel_route))
        <a href="{{ $cancel_route }}" class="btn btn-secondary btn-icon-height">
            {{ __('label.cancel') }}
        </a>
    @endif
</div>
