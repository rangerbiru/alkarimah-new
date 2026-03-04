<div class="form-group">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="{{ $attributes->get('name') }}"{{ $attributes }} @checked($old) />
        <label class="form-check-label text-muted fw-normal" for="{{ $attributes->get('name') }}">
            {!! $label !!}
        </label>
    </div>
</div>
