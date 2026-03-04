@if ($label !== false)
    @php
    $divclose = '</div>';
    $optional = ($optional) ? ' <small class="text-muted">( ' . __('label.optional') . ' )</small>' : '';
    @endphp

    <div class="form-group">
        <label>{!! $label !!}{!! $optional !!}</label>
@endif

@if ($picker_type == 'date' or $picker_type == 'year')
    <div class="input-group">
        <span class="input-group-text">
            <i class="fa-regular fa-calendar"></i>
        </span>
        <input type="text" value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control ' . $picker_type . '-picker', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>
    </div>
@else
    <input type="hidden" id="{{ $id_start }}" name="{{ $name_start }}" value="{{ $old_start }}" />
    <input type="hidden" id="{{ $id_end }}" name="{{ $name_end }}" value="{{ $old_end }}" />

    <div class="input-group">
        <span class="input-group-text">
            <i class="fa-regular fa-calendar"></i>
        </span>
        <input type="text" value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control date-range-picker', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>
    </div>
@endif

@if (!empty($info))
    <small class="text-muted">{{ $info }}</small>
@endif

{!! @$divclose !!}

@push('styles')
    @once
    <link href="{{ asset('vendors/daterange-picker/daterange-picker.css') }}" rel="stylesheet" type="text/css" />
    @endonce
@endpush

@push('scripts')
    @once
    <script src="{{ asset('vendors/daterange-picker/moment.min.js') }}"></script>
    <script src="{{ asset('vendors/daterange-picker/daterangepicker.js') }}"></script>
    @endonce

    @if ($picker_type == 'date')
        @once
            <script>
            setDatePicker()
            </script>
        @endonce
    @elseif ($picker_type == 'date-range')
        <script>
        setDateRangePicker("#{{ $id_start }}", "#{{ $id_end }}")
        </script>
    @endif
@endpush
