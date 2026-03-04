@if ($label !== false)
    @php
    $divclose = '</div>';
    $optional = ($optional) ? ' <small class="text-muted">( ' . __('label.optional') . ' )</small>' : '';
    @endphp

    <div class="form-group">
        <label>{!! $label !!}{!! $optional !!}</label>
@endif

@if ($accept_file == 'file')
    <input type="file"{{ $attributes->merge(['class' => 'form-control']) }}>
@else
    <br>
    <img class="img-thumbnail img-default-photo" src="{{ $image_default }}" style="height: {{ $image_height }};">
    <img class="img-thumbnail img-photo" style="height: {{ $image_height }};">

    <input type="file"{{ $attributes->merge(['class' => 'form-control', 'accept' => 'image/*']) }} />
@endif

@if (!empty($info))
    <small class="text-muted">{{ $info }}</small>
@endif

{!! @$divclose !!}

@if ($accept_file == 'image')
    @push('scripts')
    @once
    <script>$(".img-photo").hide()</script>
    @endonce

    <script>
    $("#{{ $attributes->get('id') }}").change(function(){
        var oFReader = new FileReader()
        var div = $(this).closest("div")

        oFReader.readAsDataURL(document.getElementById("{{ $attributes->get('id') }}").files[0])
        oFReader.onload = function (oFREvent) {
            div.find(".img-default-photo").hide()
            div.find(".img-photo").attr("src", oFREvent.target.result).show()
        }
    })
    </script>
    @endpush
@endif
