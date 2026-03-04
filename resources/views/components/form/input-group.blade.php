@if ($label !== false)
    @php
    $divclose = '</div>';
    $optional = ($optional) ? ' <small class="text-muted">( ' . __('label.optional') . ' )</small>' : '';
    @endphp

    <div class="form-group">
        <label>{!! $label . $optional !!}</label>
@endif

@if ($bootstrap == '5')
    @if ($addon_position == 'left')
        <div class="input-group">
            <span class="input-group-text">{!! $addon !!}</span>
            <input value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>
        </div>
    @elseif ($addon_position == 'right')
        <div class="input-group">
            <input value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>
            <span class="input-group-text">{!! $addon !!}</span>
        </div>
    @else
        <div class="input-group">
            <span class="input-group-text">{!! $addon !!}</span>
            <input value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>
            <span class="input-group-text">{!! $addon_end !!}</span>
        </div>
    @endif
@else
    @if ($addon_position == 'left')
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">{!! $addon !!}</span>
            </div>
            <input value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>
        </div>
    @elseif ($addon_position == 'right')
        <div class="input-group">
            <input value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>

            <div class="input-group-append">
                <span class="input-group-text">{!! $addon !!}</span>
            </div>
        </div>
    @else
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">{!! $addon !!}</span>
            </div>

            <input value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>

            <div class="input-group-append">
                <span class="input-group-text">{!! $addon_end !!}</span>
            </div>
        </div>
    @endif
@endif

@if (!empty($info))
    <small class="text-muted">{{ $info }}</small>
@endif

{!! @$divclose !!}
