@if ($label !== false)
    @php
    $divclose = '</div>';
    $optional = ($optional) ? ' <small class="text-muted">( ' . __('label.optional') . ' )</small>' : '';
    @endphp

    <div class="form-group">
        <label>{!! $label !!}{!! $optional !!}</label>
@endif

<div class="form-box">
    @php
    $index = 0;

    // Nilai tersimpan dibandingkan sebagai string: enum memakai ->value, boolean menjadi 1/0
    $current = $old;

    if ($current instanceof \BackedEnum) {
        $current = $current->value;
    } elseif ($current instanceof \UnitEnum) {
        $current = $current->name;
    }

    $current = is_bool($current) ? ($current ? '1' : '0') : (string) $current;

    // Radio pertama hanya jadi default bila belum ada nilai yang cocok dengan salah satu opsi
    $hasMatch = in_array($current, array_map('strval', array_keys($option)), true);
    @endphp

    @foreach($option as $value => $label)
        @php
        if ((string) $value === $current)
            $selected = ' checked';
        else
            $selected = (! $hasMatch && $current === '' && $index == 0) ? ' checked' : '';
        @endphp

        <div class="form-check form-check-inline">
            <input type="radio" id="{{ $attributes->get('name') . '-' . $value }}" class="form-check-input" value="{{ $value }}"{{ $attributes }}{{ $selected }}>
            <label class="form-check-label" for="{{ $attributes->get('name') . '-' . $value }}">
                {{ $label }}
            </label>
        </div>

        @php
        $index++;
        @endphp
    @endforeach
</div>

{!! @$divclose !!}
