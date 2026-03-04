@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="hr/employee/create"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('hr.employee.store.rights', $employee->encrypted_id) }}" class="form-block">
            @csrf

            <div class="table-responsive">
                <table class="table" id="table-module">
                    <thead>
                        <tr>
                            <th style="width: 30px;">
                                <div class="form-check form-check-md">
                                    <input type="checkbox" class="form-check-input" id="check-all">
                                </div>
                            </th>
                            <th>{{ __('label.module') }}</th>
                            <th>{{ __('label.description') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($module as $m)
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input type="checkbox" class="form-check-input" name="module[]" value="{{ $m->id }}"{{ (isset($module_rights[$m->id])) ? ' checked' : '' }} />
                                    </div>
                                </td>
                                <td>{{ $m->name }}</td>
                                <td>{{ $m->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-form.button-submit :cancel-route="route('hr.employee.index')" />
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $("#check-all").click(function() {
        if ($(this).is(":checked")) {
            $("#table-module tbody input[type=checkbox]").prop("checked", true)
        } else {
            $("#table-module tbody input[type=checkbox]").prop("checked", false)
        }
    })
})
</script>
@endpush
