@if ($label !== false)
    @php
    $divclose = '</div>';
    $optional = ($optional) ? ' <small class="text-muted">( ' . __('label.optional') . ' )</small>' : '';
    @endphp

    <div class="form-group">
        <label>{{ $label }}{!! $optional !!}</label>
@endif

<textarea {{ $attributes->merge(['class' => 'form-control', 'placeholder' => '.....']) }}>{{ $old }}</textarea>

@if (!empty($info))
    <small class="text-muted">{{ $info }}</small>
@endif

{!! @$divclose !!}