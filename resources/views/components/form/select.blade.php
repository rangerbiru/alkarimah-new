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
    'selected' => null,
])

@php
    // Opsi terpilih: prop selected bila dikirim, kalau tidak nilai old (nilai tersimpan / input sebelumnya).
    // Enum memakai ->value, array (select multiple) dinormalkan satu per satu
    $fromOld = is_null($selected);
    $normalize = static function ($value) use ($fromOld) {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        } elseif ($value instanceof \UnitEnum) {
            $value = $value->name;
        }

        if (is_bool($value)) {
            return $fromOld ? ($value ? '1' : '0') : strval($value);
        }

        return (string) $value;
    };

    $source = $fromOld ? $old : $selected;

    if ($source instanceof \Illuminate\Support\Collection) {
        $source = $source->all();
    }

    $selectedValues = [];

    foreach (is_array($source) ? $source : [$source] as $item) {
        if ($item === null || $item === '') {
            continue;
        }

        $selectedValues[] = $normalize($item);
    }
@endphp

<select {{ $attributes->merge(['class' => 'set-select2']) }}>
    @if (!$attributes->has('multiple'))
        <option value=""></option>
    @endif

    @foreach ($option as $value => $label)
        @php
            $isSelected = in_array((string) $value, $selectedValues, true);
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
