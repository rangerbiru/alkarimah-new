@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/report/bill-not-paid"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <div class="row mb-2">
            <div class="col-sm-7 col-md-3">
                <x-form.select
                    id="school-year"
                    :label="__('label.school_year')"
                    :option="$school_years"
                    :old="$school_year->id"
                />
            </div>
            <div class="col-sm-6 col-md-3">
                <x-form.select
                    id="month"
                    :label="__('label.month')"
                    :option="$months"
                    :old="date('n')"
                />
            </div>
            <div class="col-sm-6 col-md-3">
                <x-form.select
                    id="year"
                    :label="__('label.year')"
                    :option="$years"
                    :old="date('Y')"
                />
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-sm-6 col-md-3">
                <x-form.select
                    id="education-level"
                    :label="__('label.level_education')"
                    :option="$education_levels"
                />
            </div>
            <div class="col-sm-6 col-md-3">
                <x-form.select
                    id="class-level"
                    :label="__('label.level_class')"
                    :option="[]"
                    loading
                />
            </div>
            <div class="col-sm-6 col-md-3">
                <x-form.radio
                    id="beasiswa"
                    name="beasiswa"
                    :label="__('label.scholarship')"
                    :old="old('beasiswa', 0)"
                    :option="$yesno"
                />
            </div>
        </div>

        <button type="button" id="btn-search" class="btn btn-secondary">
            <i class="fa-solid fa-search"></i> &nbsp;{{ __('label.search') }}
        </button>
    </div>
</div>
<div class="card custom-card">
    <div class="card-body">
        <div id="progress" class="progress mb-1" role="progressbar" aria-label="Animated striped example" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="height: 20px;">
            <div class="progress-bar" style="width: 0%">0%</div>
        </div>
        <div id="btn-download" class="mb-3">
            <button type="button" id="btn-download-excel" class="btn btn-success label-btn">
                <i class="fa-solid fa-file-excel label-btn-icon me-2"></i>
                DOWNLOAD EXCEL
            </button>
            <button type="button" id="btn-download-pdf" class="btn btn-danger label-btn">
                <i class="fa-solid fa-file-pdf label-btn-icon me-2"></i>
                DOWNLOAD PDF
            </button>
        </div>

        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table" id="table-bill">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 35px;">#</th>
                        <th class="text-center" style="width: 50px;">{{ __('label.no') }}</th>
                        <th>{{ __('label.nis') }}</th>
                        <th style="min-width: 150px;">{{ __('label.name') }}</th>
                        <th style="min-width: 140px;">{{ __('label.arrears_total') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let school_year = "{{ $school_year->id }}"
let education_level = ""
let class_level = ""
let beasiswa = 0
let page = 1
let page_end = 0
let bills = {}
let no = 1
let th_arrears = 0
let id_temp_file = ""

$(document).ready(function() {
    $("#btn-download").hide()

    load()

    $("#education-level").change(function() {
        education_level = $(this).val()
        class_level = $("#class-level").val()

        optionClassLevel()
    })

    $("#class-level").change(function() {
        class_level = $(this).val()
    })

    $("#btn-search").click(function() {
        school_year = $("#school-year").val()
        education_level = $("#education-level").val()
        class_level = $("#class-level").val()
        beasiswa = $("input[name=beasiswa]:checked").val()
        page = 1
        page_end = 0
        bills = {}
        no = 1
        th_arrears = 0

        $("#table-bill tbody").empty()
        $("#table-bill .th-arrears").remove()
        $("#progress .progress-bar").css({ width: "0%" }).html("0%")
        $("#progress").attr("aria-valuenow", 0).show()

        load()
    })

    $("#btn-download-excel").click(function() {
        let url = `{{ route('finance.report.download.excel.outstanding-arrears') }}?tmp=${id_temp_file}&month=${$("#month").val()}&year=${$("#year").val()}`

        if (education_level != "")
            url += `&education=${education_level}`

        if (class_level != "")
            url += `&class=${class_level}`

        window.location = url
    })

    $("#btn-download-pdf").click(function() {
        let url = `{{ route('finance.report.download.pdf.outstanding-arrears') }}?tmp=${id_temp_file}&month=${$("#month").val()}&year=${$("#year").val()}`

        if (education_level != "")
            url += `&education=${education_level}`

        if (class_level != "")
            url += `&class=${class_level}`

        window.location = url
    })
})

function load()
{
    const month = $("#month").val()
    const year = $("#year").val()

    if (month == "" || year == "")
        return false

    const formData = {
        school_year,
        month,
        year,
        education: education_level,
        class: class_level,
        beasiswa,
        page
    }

    $.ajax({
        type: "POST",
        url: "{{ route('finance.report.get.outstanding-arrears') }}",
        data: formData,
        dataType: "json",
        success: function (response) {
            let table = ""
            let th_update = false

            response.data.student.map((s, index) => {
                let bill_total = 0

                if (s.id in bills) {
                    let table_student = ""

                    s.bills.map((b, i) => {
                        bill_total += b.total
                        table_student += `<td style="min-width: 130px;">${b.name} - Rp. ${moneyFormat(b.total)}</td>`

                        bills[s.id].bills.push(b)
                    })

                    bills[s.id].count += s.bills.length
                    bills[s.id].total += bill_total

                    if (bills[s.id].count > th_arrears) {
                        th_update = true
                        th_arrears = bills[s.id].count
                    }

                    $(`#table-bill .student-${s.id}`).append(table_student)
                    $(`#table-bill .student-${s.id} .total`).html(`Rp. ${moneyFormat(bills[s.id].total)}`)
                } else {
                    table += `<tr class="student-${s.id}">
                        <td class="text-center">
                            <a href="javascript:void(0)" class="btn btn-success btn-xs btn-whatsapp set-toolip" onclick="sendWhatsapp('${s.id}')" title="Kirim Whatsapp Tagihan" style="visibility: hidden;">
                                <i class="bx bxl-whatsapp"></i>
                            </a>
                        </td>
                        <td class="text-center">${no}</td>
                        <td>${s.nis}</td>
                        <td>${s.name}</td>`

                    let table_bill = ""

                    s.bills.map((b, i) => {
                        bill_total += b.total
                        table_bill += `<td style="min-width: 130px;">${b.name} - Rp. ${moneyFormat(b.total)}</td>`
                    })

                    if (s.bills.length > th_arrears) {
                        th_update = true
                        th_arrears = s.bills.length
                    }

                    table += `<td class="total">Rp. ${moneyFormat(bill_total)}</td>${table_bill}</tr>`
                    bills[s.id] = {
                        nis: s.nis,
                        name: s.name,
                        count: s.bills.length,
                        total: bill_total,
                        bills: s.bills
                    }
                    no++
                }
            })

            $("#table-bill tbody").append(table)

            if (th_update) {
                $("#table-bill .th-arrears").remove()
                let table = ""

                for (let t=1; t<=th_arrears; t++)
                    table += `<th class="th-arrears">{{ __('label.arrears') }} ${t}</th>`

                $("#table-bill thead tr").append(table)
            }

            if (page == 1)
                page_end = response.data.page_end

            const progress = Math.floor((page / page_end) * 100)

            $("#progress").attr("aria-valuenow", progress)
            $("#progress .progress-bar").css({ width: `${progress}%` }).html(`${progress}%`)

            if (page_end == 0) { // Data not found / empty
                $("#table-bill tbody").append(`<tr>
                    <td colspan="5">{{ __('string.data_not_found') }}</td>
                </tr>`)

                setTimeout(() => $("#progress").hide(), 1000)
                return true
            }

            if (page == page_end) {
                setTimeout(() => {
                    $("#progress").hide()
                    $("#table-bill .btn-whatsapp").css({ visibility: "visible" })
                }, 1000)
                createTempFile()

                $(".set-tooltip").tooltip({
                    container: 'body'
                })
            } else {
                page++
                load()
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            ajaxError(xhr.status)
        }
    })
}

function createTempFile()
{
    const formData = {
        content: JSON.stringify({
            count: th_arrears,
            bills
        })
    }

    $.ajax({
        type: "POST",
        url: "{{ route('attachment.store.temporary') }}",
        data: formData,
        dataType: "json",
        success: function (response) {
            id_temp_file = response.data.id

            setTimeout(() => $("#btn-download").show(), 1000)
        },
        error: function (xhr, ajaxOptions, thrownError) {
            ajaxError(xhr.status)
        }
    })
}

function sendWhatsapp(id)
{
    Swal.fire({
        title: "Sending Whatsapp",
        allowEscapeKey: false,
        allowOutsideClick: false,
        didOpen: () => {
            const formData = {
                student: id,
                bill: bills[id].bills
            }

            Swal.showLoading()

            $.ajax({
                type: "POST",
                url: "{{ route('whatsapp.send.bill-due-date') }}",
                data: formData,
                dataType: "json",
                success: function (response) {
                    setNotifSuccess(response.message, false)
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    ajaxError(xhr.status)
                }
            })
        },
    })

}

function optionClassLevel()
{
    if (education_level != "") {
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
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    }
}
</script>
@endpush
