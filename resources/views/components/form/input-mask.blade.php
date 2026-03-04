@if ($label !== false)
    @php
    $divclose = '</div>';
    $optional = ($optional) ? ' <small class="text-muted">( ' . __('label.optional') . ' )</small>' : '';
    @endphp

    <div class="form-group">
        <label>{{ $label }}{!! $optional !!}</label>
@endif

<input type="text" value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control ' . $mask . '-mask', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>

@if (!empty($info))
    <small class="text-muted">{{ $info }}</small>
@endif

{!! @$divclose !!}

@push('scripts')
    @once
    <script src="{{ asset('vendors/input-mask/inputmask.js') }}"></script>
    <script src="{{ asset('vendors/input-mask/jquery.inputmask.js') }}"></script>
    <script src="{{ asset('vendors/input-mask/inputmask.numeric.extensions.js') }}"></script>
    <script src="{{ asset('vendors/input-mask/inputmask.extensions.js') }}"></script>
    @endonce
@endpush
