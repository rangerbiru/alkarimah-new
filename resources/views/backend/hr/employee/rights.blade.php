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
                        @foreach ($module_groups as $group => $group_ids)
                            <tr class="table-light">
                                <th colspan="3">{{ __('label.module_group_' . $group) }}</th>
                            </tr>
                            @foreach ($group_ids as $group_id)
                            @continue (! isset($module[$group_id]))
                            @php $m = $module[$group_id]; @endphp
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input type="checkbox" class="form-check-input" name="module[]" value="{{ $m->id }}"{{ (isset($module_rights[$m->id])) ? ' checked' : '' }} />
                                    </div>
                                </td>
                                <td>{{ $m->name }}</td>
                                <td>
                                    {{ $m->description }}

                                    @if (in_array($m->id, $level_modules))
                                        <div class="module-levels mt-2{{ isset($module_rights[$m->id]) ? '' : ' d-none' }}" data-module="{{ $m->id }}">
                                            <div class="fw-medium mb-1">{{ __('label.level_education') }} <span class="text-danger">*</span></div>
                                            @foreach ($educations as $key => $label)
                                                <div class="form-check form-check-inline">
                                                    <input type="checkbox" class="form-check-input" name="level_education[{{ $m->id }}][]" id="level-{{ $m->id }}-{{ $key }}" value="{{ $key }}"{{ in_array($key, old('level_education.' . $m->id, $selected_educations[$m->id] ?? [])) ? ' checked' : '' }} />
                                                    <label class="form-check-label" for="level-{{ $m->id }}-{{ $key }}">{{ $label }}</label>
                                                </div>
                                            @endforeach
                                            @error('level_education.' . $m->id)
                                                <div class="text-danger text-sm">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
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
    function toggleLevels() {
        $("#table-module input[name='module[]']").each(function() {
            $(".module-levels[data-module='" + $(this).val() + "']").toggleClass("d-none", !$(this).is(":checked"))
        })
    }

    $("#table-module").on("change", "input[name='module[]']", toggleLevels)

    $("#check-all").click(function() {
        if ($(this).is(":checked")) {
            $("#table-module tbody input[name='module[]']").prop("checked", true)
        } else {
            $("#table-module tbody input[name='module[]']").prop("checked", false)
        }

        toggleLevels()
    })
})
</script>
@endpush
