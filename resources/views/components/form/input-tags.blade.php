@if ($label !== false)
    @php
    $divclose = '</div>';
    $optional = ($optional) ? ' <small class="text-muted">( ' . __('label.optional') . ' )</small>' : '';
    @endphp

    <div class="form-group">
        <label>{{ $label }}{!! $optional !!}</label>
@endif

<input type="text" value="{{ $old }}" {{ $attributes->merge(['class' => 'form-control tagsinput', 'placeholder' => '.....', 'autocomplete' => 'off']) }}>

@if (!empty($info))
    <small class="text-muted">{{ $info }}</small>
@endif

{!! @$divclose !!}

@push('styles')
    @once
    <link href="{{ asset('vendor/jquery-ui/jquery-ui.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('vendor/tags-input/jquery.tagsinput.css') }}" rel="stylesheet" type="text/css" />
    @endonce
@endpush

@push('scripts')
    @once
    <script src="{{ asset('vendor/jquery-ui/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('vendor/tags-input/jquery.tagsinput.js') }}"></script>
    @endonce

    <script>
    $("#{{ $attributes->get('id') }}").tagsInput({
        autocomplete_url : "{{ $url }}",
        width: "100%",
        height: "auto",
        delimiter: ","
    })
    </script>
@endpush
