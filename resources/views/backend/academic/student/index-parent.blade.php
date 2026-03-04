@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
<x-section-page-mobile
    :label="$title"
    :icon="$icon"
/>
@endsection

@section('content')
    @foreach ($students as $s)
        <div class="card card-tab card-student mb-3" data-id="{{ $s->encrypted_id }}">
            <div class="card-body p-3">
                <div class="d-flex">
                    <div class="me-3">
                        <img src="{{ $s->photo_url }}" style="width: 75px;" />
                    </div>
                    <div class="mt-1">
                        <h5 class="mb-1 text-info">{{ $s->name }}</h5>
                        <b class="text-grey">{{ $s->nis }}</b><br />
                        Kelas : {{ $s->class->name }}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $(".card-student").click(function() {
        let url_show = "{{ route('academic.student.show', 0) }}"
        url_show = url_show.replace("0", $(this).data("id"))

        window.location = url_show
    })
})
</script>
@endpush
