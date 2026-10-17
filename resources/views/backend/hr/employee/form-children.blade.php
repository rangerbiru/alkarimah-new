{{-- Children of the employee-as-parent: picked from all students in a modal. Shown only when "is_parent" is Yes.
     Children whose parent has an account are pickable too: the controller asks for a merge confirmation (link_preview) --}}
@if (session('link_preview'))
    @php $lp = session('link_preview'); @endphp
    <div class="alert alert-warning" id="link-confirm">
        <h6 class="mb-2">{{ __('string.link_confirm_title') }}</h6>
        <p class="mb-2">{{ __('string.link_confirm_body', ['parent' => $lp['parent']['name']]) }}</p>
        <ul class="mb-2">
            @foreach ($lp['children'] as $c)
                <li>{{ $c['name'] }} ({{ $c['nis'] }}) - {{ $c['class'] }}</li>
            @endforeach
        </ul>
        @foreach ($lp['warnings'] as $w)
            <p class="mb-1 text-danger">{{ $w }}</p>
        @endforeach
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="confirm_link_parent" name="confirm_link_parent" value="{{ $lp['token'] }}">
            <label class="form-check-label" for="confirm_link_parent">{{ __('string.link_confirm_check') }}</label>
        </div>
        <small class="text-muted">{{ __('string.link_confirm_hint') }}</small>
    </div>
@endif

<div id="children-box" class="mb-4">
    <label>{{ __('label.children') }}</label>

    <div id="children-list" class="d-flex flex-wrap gap-2 mb-2">
        @foreach ($children as $child)
            @php $locked = in_array($child->id, $linked_children); @endphp
            <span class="badge bg-primary-transparent fs-13 d-inline-flex align-items-center" data-id="{{ $child->id }}">
                {{ $child->name }} ({{ $child->nis }})
                <input type="hidden" name="children[]" value="{{ $child->encrypted_id }}">
                @unless ($locked)
                    <i class="bx bx-x ms-1 remove-child" role="button"></i>
                @endunless
            </span>
        @endforeach
    </div>

    <small class="text-muted d-block mb-2" id="children-empty" @if ($children->isNotEmpty()) style="display: none !important;" @endif>
        {{ __('string.no_children_selected') }}
    </small>

    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-children">
        <i class="bx bx-search"></i> {{ __('label.select_children') }}
    </button>
</div>

<div class="modal fade" id="modal-children" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">{{ __('label.select_children') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table id="table-children" class="table table-bordered text-nowrap w-100">
                    <thead>
                        <tr>
                            <th>{{ __('label.nis') }}</th>
                            <th>{{ __('label.name') }}</th>
                            <th>{{ __('label.class') }}</th>
                            <th>{{ __('label.parent') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}" type="text/javascript"></script>
    <script>
        $(document).ready(function() {
            const label_choose = "{{ __('label.choose') }}"
            const label_selected = "{{ __('string.child_selected') }}"
            const label_locked = "{{ __('string.child_has_parent_account') }}"
            let table_children = null

            function toggleChildrenBox() {
                $("#children-box").toggle($("input[name='is_parent']:checked").val() == "1")
            }

            function isSelected(encrypted_id, name) {
                // Encrypted ids differ per request, so compare the visible "name (nis)" text too
                return $("#children-list input[value='" + encrypted_id + "']").length > 0 ||
                    $("#children-list span").filter((i, el) => $(el).text().trim() == name).length > 0
            }

            function refreshEmpty() {
                $("#children-empty").attr("style", $("#children-list span").length ? "display: none !important;" : "")
            }

            $("#modal-children").on("shown.bs.modal", function() {
                if (table_children) {
                    table_children.columns.adjust()
                    return
                }

                table_children = $("#table-children").DataTable({
                    processing: true,
                    serverSide: true,
                    ordering: false,
                    ajax: {
                        url: "{{ route('hr.employee.datatable.student') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            employee: "{{ isset($employee) ? $employee->encrypted_id : '' }}"
                        }
                    },
                    columns: [
                        { data: "nis" },
                        { data: "name" },
                        { data: "class" },
                        { data: "parent" },
                        {
                            data: null,
                            className: "text-center",
                            render: function(row) {
                                const label = row.name + " (" + row.nis + ")"

                                if (isSelected(row.encrypted_id, label))
                                    return '<span class="badge bg-success">' + label_selected + '</span>'

                                // The parent has an account: still pickable, the form asks for a merge confirmation
                                const note = row.has_account ? '<span class="badge bg-light text-muted me-1">' + label_locked + '</span>' : ''

                                return note + '<button type="button" class="btn btn-primary btn-sm pick-child">' + label_choose + '</button>'
                            }
                        }
                    ]
                })
            })

            $("#table-children").on("click", ".pick-child", function() {
                const row = table_children.row($(this).closest("tr")).data()
                const chip = $('<span class="badge bg-primary-transparent fs-13 d-inline-flex align-items-center"></span>')

                chip.text(row.name + " (" + row.nis + ")")
                chip.append($('<input type="hidden" name="children[]">').val(row.encrypted_id))
                chip.append('<i class="bx bx-x ms-1 remove-child" role="button"></i>')

                $("#children-list").append(chip)
                $(this).replaceWith('<span class="badge bg-success">' + label_selected + '</span>')
                refreshEmpty()
            })

            $("#children-list").on("click", ".remove-child", function() {
                $(this).closest("span").remove()
                refreshEmpty()

                if (table_children)
                    table_children.draw(false)
            })

            $("input[name='is_parent']").on("change", toggleChildrenBox)
            toggleChildrenBox()
        })
    </script>
@endpush
