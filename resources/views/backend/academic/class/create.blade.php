@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/class/create"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('academic.class.store') }}" class="form-block">
            @csrf

            <div class="row">
                <div class="col-md-4">
                    <x-form.input-text
                        name="name"
                        :label="__('label.name')"
                        :old="old('name')"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <x-form.select
                        name="id_wali_kelas"
                        :label="__('label.wali_kelas')"
                        :option="$wali_kelas"
                        :old="old('id_wali_kelas')"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="education-level"
                        name="level_education"
                        :label="__('label.level_education')"
                        :old="old('level_education')"
                        :option="$educations"
                    />
                </div>
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        id="class-level"
                        name="level_class"
                        :label="__('label.level_class')"
                        :old="old('level_class')"
                        :option="[]"
                        loading
                    />
                </div>
            </div>

            <x-form.button-submit :cancel-route="route('academic.class.index')" />
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const error = "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"
let education_level = "{{ old('level_education') }}"
let class_level = "{{ old('level_class') }}"

$(document).ready(function() {
    if (error != "")
        setNotifInfo(error)

    optionClassLevel()

    $("#education-level").change(function() {
        education_level = $(this).val()
        optionClassLevel()
    })
})

function optionClassLevel(){
    if(education_level != ""){
        $("#loading-class-level").show()

        const formData = {level: education_level}

        $.ajax({
            type: "POST",
            url: "{{ route('academic.class.get.option.level') }}",
            data: formData,
            dataType: "json",
            success: function(response) {
                $("#loading-class-level").hide()
                $("#class-level").html(response.option).trigger("change.select2")

                if(class_level != "")
                    $("#class-level").val(class_level).trigger("change.select2")
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    }
}
</script>
@endpush
