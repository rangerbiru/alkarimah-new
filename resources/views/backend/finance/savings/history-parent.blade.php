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
    <div class="card-body p-2">
        @include($path . 'menu')

        <div class="mt-3 mb-1">
            <x-form.input-text
                id="search"
                :placeholder="__('label.search2') . '...'"
            />

            <a href="javascript:void(0)" id="btn-search" class="text-muted" style="position: absolute;right: 18px;margin-top: -27px;">
                <i class="fa-solid fa-search"></i>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-2">
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
    </div>
</div>
@endsection

@push('styles')
<style>
    .card-history {
        font-size: 11px;
        border-bottom: 1px solid var(--input-border);
        padding-bottom: 10px;
        margin-bottom: 10px;
    }
</style>
@endpush

@push('scripts')
<script>
const status_paid = "{{ $status_paid }}"
let page = 1
let loading = false

$(document).ready(function() {
    load()

    $("#search").keyup(function() {
        const keyboard = event.which || event.keyCode

        if (keyboard == 13) {
            page = 1
            load(true)
        }
    }).blur(function() {
        page = 1
        load(true)
    })

    $("#btn-search").click(function() {
        page = 1
        load(true)
    })

    $("#list").on("click", ".card-history", function() {
        if ($(this).data("flag") == 1) { // Deposit
            let url = "{{ route('finance.savings.waiting', 0) }}"

            if ($(this).data("status") == status_paid)
                url = "{{ route('finance.savings.show', 0) }}"
        } else
            url = "{{ route('finance.savings.show-withdrawal', 0) }}"

        url = url.replace("0", $(this).data("id"))
        window.location = url
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
    const formData = {page, search: $("#search").val()}
    loading = true

    $("#error").hide()
    $("#loading").show()

    $.ajax({
        type: "POST",
        url: "{{ route('finance.savings.get.history') }}",
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
