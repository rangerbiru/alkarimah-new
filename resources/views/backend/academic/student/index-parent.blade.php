@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
<x-section-page-mobile
    :label="$title"
    :icon="$icon"
/>
@endsection

@section('content')
    @if ($can_add_child)
        <button type="button" class="btn btn-warning text-white shadow-lg position-fixed" data-bs-toggle="modal"
            data-bs-target="#modal-add-child"
            style="bottom: 85px; right: 20px; z-index: 1050; border-radius: 50px; padding: 12px 24px; transition: 0.3s;">
            <div class="d-flex align-items-center">
                <i class="bx bx-plus fs-4 me-1"></i>
                <span class="fw-semibold">{{ __('label.add_child') }}</span>
            </div>
        </button>
    @endif

    @forelse ($students as $s)
        <div class="card card-tab card-student mb-3" data-id="{{ $s->encrypted_id }}">
            <div class="card-body p-3">
                <div class="d-flex">
                    <div class="me-3">
                        <img src="{{ $s->photo_url }}" style="width: 75px;" />
                    </div>
                    <div class="mt-1">
                        <h5 class="mb-1 text-info">{{ $s->name }}</h5>
                        <b class="text-grey">{{ $s->nis }}</b><br />
                        Kelas : {{ $s->class?->name ?? '-' }}
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center text-muted">{{ __('string.no_children_yet') }}</div>
        </div>
    @endforelse

    @if ($can_add_child)
        <div class="modal fade" id="modal-add-child" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" action="{{ route('academic.student.store.child') }}">
                        @csrf

                        <div class="modal-header">
                            <h6 class="modal-title">{{ __('label.add_child') }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted">{{ __('string.add_child_info') }}</p>

                            <x-form.input-text name="nis" :label="__('label.nis')" :old="old('nis')" />
                            <x-form.date-picker name="birthdate" picker-type="date" :label="__('label.birthdate')" :old="old('birthdate')" />
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light"
                                data-bs-dismiss="modal">{{ __('label.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('label.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $(".card-student").click(function() {
        let url_show = "{{ route('academic.student.show', 0) }}"
        url_show = url_show.replace("0", $(this).data("id"))

        window.location = url_show
    })

    @if ($errors->any())
        setNotifFail("{{ $errors->first() }}")
    @endif
})
</script>
@endpush
