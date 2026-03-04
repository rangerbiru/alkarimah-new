@if ($label !== false)
    @php
    $divclose = '</div>';
    $optional = ($optional) ? ' <small class="text-muted">( ' . __('label.optional') . ' )</small>' : '';
    @endphp

    <div class="form-group">
        <label>{{ $label }}{!! $optional !!}</label>
@endif

<input value="{{ $old }}" {{ $attributes->merge(['type' => 'text', 'class' => 'form-control', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>

@if (!empty($info))
    <small class="text-muted">{{ $info }}</small>
@endif

{!! @$divclose !!}