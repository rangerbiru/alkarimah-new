@if ($label !== false)
    @php
    $divclose = '</div>';
    $optional = ($optional) ? ' <small class="text-muted">( ' . __('label.optional') . ' )</small>' : '';
    @endphp

    <div class="form-group">
        <label>{{ $label }}{!! $optional !!}</label>
@endif

<textarea placeholder="....."{{ $attributes->merge(['class' => 'form-control']) }}>{{ $old }}</textarea>

@if (!empty($info))
    <small class="text-muted">{{ $info }}</small>
@endif

{!! @$divclose !!}

@push('scripts')
@once
<script src="{{ asset('vendor/ckeditor/ckeditor.js') }}"></script>
@endonce

<script>
setTextEditor("{{ $attributes->get('id') }}")
</script>
@endpush
