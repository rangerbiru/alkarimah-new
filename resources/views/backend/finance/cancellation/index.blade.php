@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="finance/cancellation" />
@endsection

@section('content')
    <div class="card custom-card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6 col-md-4">
                    <x-form.date-picker id-start="start" id-end="end" name-start="start" name-end="end"
                        picker-type="date-range" :old="$filter->start" :old-end="$filter->end" />
                </div>
                <div class="col-sm-6 col-md-4">
                    <x-form.select id="type" :option="$types" :data-placeholder="__('label.all_transaction')" data-allow-clear="true" />
                </div>
                <div class="col-sm-6 col-md-4">
                    <button type="button" id="btn-search" class="btn btn-secondary">
                        <i class="fa-solid fa-search"></i> &nbsp;{{ __('label.search') }}
                    </button>
                    <a href="{{ route('finance.cancellation.history') }}" class="btn btn-outline-primary">
                        <i class="bx bx-history"></i> &nbsp;{{ __('label.cancellation_history') }}
                    </a>
                </div>
            </div>
            <small class="text-muted d-block mt-3">{{ __('string.cancel_search_hint') }}</small>
        </div>
    </div>
    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="table-cancellation">
                    <thead>
                        <tr>
                            <th style="width: 50px;">{{ __('label.no') }}</th>
                            <th>{{ __('label.transaction_number') }}</th>
                            <th>{{ __('label.transaction_type') }}</th>
                            <th>{{ __('label.student_or_parent_or_person') }}</th>
                            <th>{{ __('label.payment') }}</th>
                            <th>{{ __('label.total') }}</th>
                            <th>{{ __('label.status') }}</th>
                            <th style="width: 100px;">#</th>
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

@php
    $cancel_lang = [
        'title' => __('string.cancel_confirm_title', ['number' => ':number']),
        'placeholder' => __('string.cancel_reason_placeholder', ['words' => ':words', 'chars' => ':chars']),
        'bills_back' => __('string.cancel_bills_back'),
        'parent_balance' => __('string.cancel_parent_balance'),
        'student_savings' => __('string.cancel_student_savings'),
        'unique_code_back' => __('string.cancel_unique_code_back'),
        'refund_transfer' => __('string.cancel_refund_transfer', ['amount' => ':amount']),
        'refund_balance' => __('string.cancel_refund_balance', ['amount' => ':amount']),
        'cannot' => __('string.cancel_cannot'),
        'reason_visible' => __('string.cancel_reason_visible'),
        'type' => __('label.transaction_type'),
        'paid_at' => __('label.payment_date'),
        'total' => __('label.total'),
        'student' => __('label.student'),
        'parent' => __('label.parent'),
        'cancel' => __('label.cancel_transaction'),
        'close' => strtoupper(__('label.cancel')),
        'reason' => __('label.reason'),
    ];
@endphp

@push('scripts')
    <script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}"
        type="text/javascript"></script>

    <script>
        const reason_rule = @json($reason);
        const cancel_lang = @json($cancel_lang);
        const url_preview = "{{ route('finance.cancellation.preview', 0) }}";
        const url_store = "{{ route('finance.cancellation.store', 0) }}";
        const url_index = "{{ route('finance.cancellation.index') }}";
        const type_names = @json($types);

        let start_date = "{{ $filter->start }}";
        let end_date = "{{ $filter->end }}";
        let type = "";

        const rupiah = (n) => "Rp " + moneyFormat(n);
        const dateTime = (d) => dateFormat(d, "{dd} {mmm} {yyyy} {hh}:{ii}");

        function el(tag, className, text) {
            const node = document.createElement(tag);
            if (className) node.className = className;
            if (text !== undefined) node.textContent = text;
            return node;
        }

        // Satu bagian dialog: judul tebal abu-abu, isi di bawahnya (teks selalu lewat textContent)
        function section(title, lines) {
            const box = el("div", "mb-3");
            const heading = el("div", "fw-semibold", title);
            heading.style.color = "#6b7280";
            box.appendChild(heading);
            [].concat(lines).forEach((l) => box.appendChild(el("div", "", l)));
            return box;
        }

        function movement(m) {
            return rupiah(m.before) + " → " + rupiah(m.after);
        }

        function buildPreview(d) {
            const box = el("div", "text-start");

            if (!d.can_cancel) {
                const alert = el("div", "alert alert-danger");
                alert.appendChild(el("div", "fw-bold mb-1", cancel_lang.cannot));
                const ul = el("ul", "mb-0");
                d.blockers.forEach((b) => ul.appendChild(el("li", "", b.message)));
                alert.appendChild(ul);
                box.appendChild(alert);
            }

            if (d.warnings && d.warnings.length) {
                const alert = el("div", "alert alert-warning");
                d.warnings.forEach((w) => alert.appendChild(el("div", "", w.message ?? w)));
                box.appendChild(alert);
            }

            const t = d.transaction;
            if (t.number) {
                box.appendChild(section(type_names[t.flag] ?? t.flag, [
                    cancel_lang.paid_at + ": " + dateTime(t.paid_at),
                    cancel_lang.total + ": " + rupiah(t.total),
                ]));
            }
            if (d.student) box.appendChild(section(cancel_lang.student, d.student.name + " (" + d.student.nis + ")"));
            if (d.parent) box.appendChild(section(cancel_lang.parent, d.parent.name));

            const bills = (d.details && d.details.bills) || [];
            if (bills.length) {
                box.appendChild(section(cancel_lang.bills_back,
                    bills.map((b) => b.name + (b.period ? " " + b.period : "") + " · " + rupiah(b.total))));
            }

            if (d.parent_balance) box.appendChild(section(cancel_lang.parent_balance, movement(d.parent_balance)));
            if (d.student_savings) box.appendChild(section(cancel_lang.student_savings, movement(d.student_savings)));
            if (d.unique_code_reversed > 0) box.appendChild(section(cancel_lang.unique_code_back, rupiah(d.unique_code_reversed)));

            if (d.refund) {
                box.appendChild(el("div", "alert alert-info mb-0",
                    (d.refund.mode === "balance" ? cancel_lang.refund_balance : cancel_lang.refund_transfer)
                    .replace(":amount", moneyFormat(d.refund.amount))));
            }

            return box;
        }

        function reasonProblem(value) {
            const text = (value || "").replace(/\s+/g, " ").trim();
            const words = text === "" ? 0 : text.split(" ").length;
            if (words < reason_rule.min_words || text.length < reason_rule.min_chars) {
                return cancel_lang.placeholder.replace(":words", reason_rule.min_words).replace(":chars", reason_rule.min_chars);
            }
            return null;
        }

        function cancelTransaction(id, number) {
            $.ajax({
                type: "POST",
                url: url_preview.replace(/\/0$/, "/" + encodeURIComponent(id)),
                dataType: "json",
                success: function(response) {
                    if (!response.status) return setNotifInfo(response.message);

                    const d = response.data;
                    const options = {
                        title: cancel_lang.title.replace(":number", number),
                        icon: "warning",
                        html: buildPreview(d),
                        width: 640,
                        showCancelButton: true,
                        cancelButtonText: cancel_lang.close,
                        confirmButtonText: cancel_lang.cancel,
                        showConfirmButton: d.can_cancel,
                    };

                    if (d.can_cancel) {
                        Object.assign(options, {
                            input: "textarea",
                            inputLabel: cancel_lang.reason_visible,
                            inputPlaceholder: cancel_lang.placeholder.replace(":words", reason_rule.min_words).replace(":chars", reason_rule.min_chars),
                            inputValidator: (value) => reasonProblem(value),
                            showLoaderOnConfirm: true,
                            allowOutsideClick: () => !Swal.isLoading(),
                            preConfirm: (reason) => $.ajax({
                                type: "POST",
                                url: url_store.replace(/\/0$/, "/" + encodeURIComponent(id)),
                                dataType: "json",
                                data: { reason: reason },
                            }).then(
                                (res) => res,
                                (xhr) => {
                                    if (xhr.status === 422 || xhr.status === 403) {
                                        Swal.showValidationMessage(xhr.responseJSON.message);
                                    } else {
                                        Swal.close();
                                        ajaxError(xhr.status);
                                    }
                                    return false;
                                }
                            ),
                        });
                    }

                    Swal.fire(options).then((result) => {
                        if (result.isConfirmed && result.value && result.value.status) {
                            setNotifSuccess(result.value.message, false);
                            window.LaravelDataTables["table-cancellation"].ajax.reload(null, false);
                        }
                    });
                },
                error: function(xhr) {
                    if (xhr.status === 422 || xhr.status === 403) return setNotifInfo(xhr.responseJSON.message);
                    ajaxError(xhr.status);
                }
            });
        }

        $(document).ready(function() {
            window.LaravelDataTables = window.LaravelDataTables || {};
            window.LaravelDataTables["table-cancellation"] = $("#table-cancellation").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('finance.cancellation.datatable') }}",
                    type: "POST",
                    data: (d) => {
                        d.start_date = start_date;
                        d.end_date = end_date;
                        d.type = type;

                        return d;
                    }
                },
                processing: true,
                serverSide: true,
                deferRender: true,
                ordering: false,
                aLengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                columns: [{
                        class: "align-middle",
                        searchable: false,
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row) => `<span class="fw-bold">${htmlEntities(row.number)}</span>
                            <div class="mt-3"><small>${dateTime(row.paid_at)}</small></div>`
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row) => htmlEntities(row.type_name)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row) => {
                            let html = row.student ? htmlEntities(row.student.name) : (row.parent ? htmlEntities(row.parent.name) : "-");

                            if (row.student) {
                                html += `<div class="mt-3"><small><b>{{ __('label.nis') }} ${htmlEntities(row.student.nis)}</b></small></div>`;
                            }

                            return html;
                        }
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row) => htmlEntities(row.method)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row) => rupiah(row.total)
                    },
                    {
                        class: "align-middle",
                        render: (data, type, row) => row.cancelled ?
                            `<span class="badge bg-danger">{{ strtoupper(__('label.cancelled')) }}</span>` :
                            `<span class="badge bg-success">{{ strtoupper(__('label.paid_off')) }}</span>`
                    },
                    {
                        class: "align-middle text-center",
                        searchable: false,
                        render: (data, type, row) => row.cancelled ? "" :
                            `<button type="button" class="btn btn-danger btn-sm text-nowrap btn-cancel" data-id="${htmlEntities(row.encrypted_id)}" data-number="${htmlEntities(row.number)}">
                                <i class="bx bx-revision me-1"></i>{{ strtoupper(__('label.cancel_transaction')) }}
                            </button>`
                    }
                ]
            });

            $($.fn.dataTable.tables(true)).css('width', '100%');

            $("#table-cancellation").on("click", ".btn-cancel", function() {
                cancelTransaction($(this).data("id"), $(this).data("number"));
            });

            $("#btn-search").click(function() {
                start_date = $("#start").val();
                end_date = $("#end").val();
                type = $("#type").val() || "";

                window.history.pushState("", "", `${url_index}?start=${start_date}&end=${end_date}`);
                window.LaravelDataTables["table-cancellation"].ajax.reload();
            });
        });
    </script>
@endpush
