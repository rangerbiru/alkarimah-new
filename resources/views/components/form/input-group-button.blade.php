@if ($label !== false)
    @php
    $divclose = '</div>';
    $optional = ($optional) ? ' <small class="text-muted">( ' . __('label.optional') . ' )</small>' : '';
    @endphp

    <div class="form-group">
        <label>{!! $label . $optional !!}</label>
@endif

@if ($bootstrap == '5')
    @if ($button_position == 'left')
        <div class="input-group">
            <button type="{{ $button_type }}" id="{{ $button_id }}" class="{{ $button_class }}">
                {!! $button_label !!}
            </button>
            <input type="text" value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>
        </div>
    @else
        <div class="input-group">
            <input type="text" value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>
            <button type="{{ $button_type }}" id="{{ $button_id }}" class="{{ $button_class }}">
                {!! $button_label !!}
            </button>
        </div>
    @endif
@else
    @if ($button_position == 'left')
        <div class="input-group">
            <div class="input-group-prepend">
                <button type="{{ $button_type }}" id="{{ $button_id }}" class="{{ $button_class }}">
                    {!! $button_label !!}
                </button>
            </div>

            <input type="text" value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>
        </div>
    @else
        <div class="input-group">
            <input type="text" value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>

            <div class="input-group-append">
                <button type="{{ $button_type }}" id="{{ $button_id }}" class="{{ $button_class }}">
                    {!! $button_label !!}
                </button>
            </div>
        </div>
    @endif
@endif

@if (!empty($info))
    <small class="text-muted">{{ $info }}</small>
@endif

{!! @$divclose !!}
