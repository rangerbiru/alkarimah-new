@if ($label !== false)
    @php
        $divclose = '</div>';
        $optional = $optional ? ' <small class="text-muted">( ' . __('label.optional') . ' )</small>' : '';
    @endphp

    <div class="form-group">
        <label>{!! $label !!}{!! $optional !!}</label>
@endif

@props([
    'option' => [],
    'old' => null,
    'selected' => [],
])

@php
    if (is_null($selected)) {
        $selectedValues = $old !== null ? (array) $old : [];
    } else {
        $selectedValues = is_array($selected) ? $selected : [$selected];
    }

@endphp

<select {{ $attributes->merge(['class' => 'set-select2']) }}>
    @if (!$attributes->has('multiple'))
        <option value=""></option>
    @endif

    @foreach ($option as $value => $label)
        @php
            $valStr = (string) $value;
            $isSelected = in_array($valStr, array_map('strval', $selectedValues), true);
        @endphp

        <option value="{{ $value }}" {{ $isSelected ? 'selected' : '' }}>
            {{ $label }}
        </option>
    @endforeach
</select>


@if (!empty($info))
    <small class="text-muted">{{ $info }}</small>
@endif

@if ($loading)
    <div class="loading-option" id="loading-{{ $attributes->get('id') }}">
        <img src="{{ asset('images/loader.gif') }}">
    </div>
@endif

{!! @$divclose !!}

@push('styles')
    @once
        <link rel="stylesheet" href="{{ asset('vendors/select2/css/select2.min.css') }}">
    @endonce
@endpush

@push('scripts')
    @once
        <script src="{{ asset('vendors/select2/js/select2.full.min.js') }}"></script>

        @if ($init == 'true')
            <script>
                setSelect2()
            </script>
        @endif
    @endonce
@endpush
