@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
<x-section-page-mobile
    :label="$title"
    :icon="$icon"
/>
@endsection

@section('content')
<div class="card card-tab">
    <div class="card-body p-3">
        <form method="post" action="{{ route('academic.absence.store') }}" class="form-block">
            @csrf

            <x-form.date-picker
                name="dates"
                :label="__('label.date')"
                :old="old('dates', date('d-m-Y'))"
                picker-type="date"
            />
            <x-form.select
                name="id_type"
                id="type"
                :label="__('label.absence_type')"
                :option="$types"
                :old="old('id_type')"
            />

            <x-section-form
                icon="fa-solid fa-user-circle"
                :label="__('label.student_list')"
            />

            <div id="student-list">
                <small class="text-muted">Pilih Jenis Absensi untuk memulai</small>
            </div>

            <x-form.button-submit class="btn-block" />
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $("#type").change(function() {
        const formData = { type: $(this).val() }

        $.ajax({
            type: "POST",
            url: "{{ route('academic.absence.get.student') }}",
            data: formData,
            dataType: "json",
            success: function (response) {
                $("#student-list").html(response.data.list)
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    })
})
</script>
@endpush

@push('styles')
<link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">

<style>
    .form-box-student {
        border: 1px solid var(--input-border);
        border-radius: 5px;
        padding: 0.5rem 0.5rem;
    }
    .form-box-student .form-check-md .form-check-input {
        margin-top: 10px;
    }
</style>
@endpush
