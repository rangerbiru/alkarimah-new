@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/parent"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <x-section-page-action
            :label="$title"
            :icon="$icon"
            :count="$count"
            :create-route="route('academic.parent.create')"
        />

        <div class="table-responsive">
            <table class="table" id="table-parent">
                <thead>
                    <tr>
                        <th>{{ __('label.no') }}</th>
                        <th>{{ __('label.name') }}</th>
                        <th>{{ __('label.phone_number') }}</th>
                        <th>{{ __('label.gender') }}</th>
                        <th style="width: 300px;">{{ __('label.address') }}</th>
                        <th>{{ __('label.husband_or_wife') }}</th>
                        <th style="width: 70px;">#</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}" type="text/javascript"></script>

<script>
$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}
    window.LaravelDataTables["table-parent"] = $("#table-parent").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('academic.parent.datatable') }}",
            type: "POST"
        },
        processing: true,
        serverSide: true,
        deferRender: true,
        ordering: false,
        aLengthMenu: [[10, 25, 50, 100],[10, 25, 50, 100]],
        drawCallback: function() {
            $(".set-tooltip").tooltip({
                container: "body"
            })
        },
        columns: [
            {
                class: "align-middle",
                width: "50px",
                searchable: false,
                render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => htmlEntities(row.name)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => (row.phone == "") ? "-" : phoneFormat(row.phone)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => row.gender_name
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => row.address_full
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => (row.id_relation == null) ? "-" : row.relation.name
            },
            {
                class: "align-middle text-center",
                searchable: false,
                render: function(data, type, row) {
                    let url_edit = "{{ route('academic.parent.edit', 0) }}"
                    let url_destroy = "{{ route('academic.parent.destroy', 0) }}"
                    let url_preview = "{{ route('academic.parent.delete-preview', 0) }}"

                    url_edit = url_edit.replace("0", row.encrypted_id)
                    url_destroy = url_destroy.replace("0", row.encrypted_id)
                    url_preview = url_preview.replace("0", row.encrypted_id)

                    return `<a href="${url_edit}" class="btn btn-dark btn-xs set-tooltip" title="${label_edit}">
                            <i class="bx bx-pencil"></i>
                        </a>
                        <a href="javascript:void(0)" class="btn btn-danger btn-xs set-tooltip" title="${label_delete}" onclick="deleteParent('${url_preview}', '${url_destroy}')">
                            <i class="bx bx-trash"></i>
                        </a>`
                }
            }
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')
});

const string_account_notice = {{ Js::from(__('message.parent_delete_account_notice')) }};
const string_deactivate_option = {{ Js::from(__('message.parent_delete_deactivate_option')) }};
const string_alias_option = {{ Js::from(__('message.parent_delete_alias_option')) }};
const string_alias_hint = {{ Js::from(__('message.parent_delete_alias_hint')) }};

// Konfirmasi hapus orang tua: beri tahu bila akun login-nya ikut dinonaktifkan, dan bila ada pegawai
// bernomor HP sama, tawarkan (bukan otomatis) menjadikan akun itu alias dari akun pegawai tsb
function deleteParent(urlPreview, urlDestroy) {
    $.get(urlPreview, function (response) {
        const data = response.data || {}
        const candidates = data.candidates || []
        let html = htmlEntities(string_confirm_delete)
        let inputOptions = undefined

        if (data.account) {
            html += '<br><br><b>' + htmlEntities(string_account_notice) + '</b>'

            if (candidates.length > 0) {
                html += '<br><small class="text-muted">' + htmlEntities(string_alias_hint) + '</small>'
                inputOptions = { none: string_deactivate_option }

                candidates.forEach(function (candidate) {
                    inputOptions[candidate.id] = string_alias_option.replace(':name', candidate.name + ' (' + candidate.nip + ')')
                })
            }
        }

        Swal.fire({
            icon: "warning",
            title: label_confirmation,
            html: html,
            input: inputOptions ? "radio" : undefined,
            inputOptions: inputOptions,
            inputValue: "none",
            showCancelButton: true,
            confirmButtonText: label_yes,
            cancelButtonText: label_cancel,
            showLoaderOnConfirm: true,
            preConfirm: (choice) => {
                const alias = (inputOptions && choice && choice !== "none") ? choice : ""

                return $.ajax({
                    type: "DELETE",
                    url: urlDestroy,
                    data: { alias_employee: alias },
                    dataType: "json"
                }).then(function (result) {
                    if (result.status) {
                        window.LaravelDataTables["table-parent"].ajax.reload()
                        setNotifSuccess(result.message, false)
                    } else {
                        setNotifInfo(result.message)
                    }
                }).catch(function (xhr) {
                    ajaxError(xhr.status)
                })
            }
        })
    }).fail(function (xhr) {
        ajaxError(xhr.status)
    })
}
</script>
@endpush
