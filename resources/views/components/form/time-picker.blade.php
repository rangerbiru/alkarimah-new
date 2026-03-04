@if ($label !== false)
    @php
        $divclose = '</div>';
        $optional = $optional ? ' <small class="text-muted">( ' . __('label.optional') . ' )</small>' : '';
    @endphp

    <div class="form-group">
        <label>{!! $label !!}{!! $optional !!}</label>
@endif

<div class="input-group">
    <span class="input-group-text">
        <i class="fa-regular fa-clock"></i>
    </span>
    <input type="text" value="{{ $old }}"
        {{ $attributes->merge(['class' => 'form-control time-picker', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>
</div>

@if (!empty($info))
    <small class="text-muted">{{ $info }}</small>
@endif

{!! @$divclose !!}

@push('styles')
    @once
        <link href="{{ asset('vendor/time-picker/timepicker.css') }}" rel="stylesheet" />
    @endonce
@endpush

@push('scripts')
    @once
        <script src="{{ asset('vendor/time-picker/jquery-clockpicker.min.js') }}"></script>
        <script src="{{ asset('vendor/time-picker/highlight.min.js') }}"></script>
        <script src="{{ asset('vendor/time-picker/clockpicker.js') }}"></script>
    @endonce

    @once
        <script>
            setTimePicker()
        </script>
    @endonce
@endpush
