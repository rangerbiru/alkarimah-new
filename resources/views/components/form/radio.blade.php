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
    @endphp

    @foreach($option as $value => $label)
        @php
        if ($value == $old)
            $selected = ' checked';
        else
            $selected = ($old == '' && $index == 0) ? ' checked' : '';
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
