@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
<x-section-page-mobile
    :label="$title"
    :icon="$icon"
/>
@endsection

@section('content')
<div class="card card-tab mb-3">
    <div class="card-body p-2 pb-0">
        <x-form.date-picker
            id="date"
            picker-type="date"
            :label="__('label.filter_date')"
            :old="$date"
        />

        <a href="javascript:void(0)" id="btn-search" class="text-muted" style="position: absolute;right: 18px;margin-top: -43px;">
            <i class="fa-solid fa-search"></i>
        </a>
    </div>
</div>

<div id="list"></div>
<div id="loading" class="text-center">
    <img src="{{ asset('images/loader.gif') }}" style="width: 50px" />
</div>
<div id="error" class="text-center text-muted my-4">
    <i class="fa-solid fa-exclamation-circle fa-lg"></i>
    <div class="mt-1">
        {{ __('string.something_went_wrong') }}
    </div>
</div>
@endsection

@push('scripts')
<script>
let date = "{{ $date }}"
let page = 1
let loading = false

$(document).ready(function() {
    load()

    $("#date").on("apply.daterangepicker", function (ev, picker) {
        page = 1
        load(true)
    })

    $("#btn-search").click(function() {
        page = 1
        load(true)
    })
})

$(window).scroll(function () {
    if ($(window).scrollTop() >= $(document).height() - $(window).height() - 22) {
        if (loading == false) {
            load(false)
        }
    }
})

function load(reset)
{
    const formData = {
        page,
        date: $("#date").val(),
        students: JSON.parse('{{ $students }}')
    }

    loading = true

    $("#error").hide()
    $("#loading").show()

    $.ajax({
        type: "POST",
        url: "{{ route('service.activity.get') }}",
        data: formData,
        dataType: "json",
        success: function (response) {
            $("#loading").hide()

            if (response.data.count > 0) {
                page++
                loading = false

                if (reset)
                    $("#list").html(response.data.list)
                else
                    $("#list").append(response.data.list)
            } else {
                if (reset)
                    $("#list").html(response.data.list)
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            loading = false
            $("#loading").hide()
            $("#error").show()
        }
    })
}
</script>
@endpush

@push('styles')
<style>
    .card-history .icon {
        background: var(--input-border);
        border-radius: 50%;
        padding: 6px 10px;
        font-size: 25px;
        margin-right: 10px;
    }
    .card-history .text {
        font-size: 12px;
        line-height: 15px;
        padding-top: 3px;
    }
</style>
@endpush
