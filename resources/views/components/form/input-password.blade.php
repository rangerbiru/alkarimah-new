@if ($label !== false)
    @php
    $divclose = '</div>';
    $optional = ($optional) ? ' <small class="text-muted">( ' . __('label.optional') . ' )</small>' : '';
    @endphp

    <div class="form-group">
        <label>{{ $label }}{!! $optional !!}</label>
@endif

<input type="password" value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control', 'placeholder' => '.....', 'autocomplete' => 'new-password']) }}>

@if (!empty($info))
    <small class="text-muted">{{ $info }}</small>
@endif

{!! @$divclose !!}