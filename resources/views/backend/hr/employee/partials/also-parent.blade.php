{{-- Bagian "Juga Orang Tua" di form pegawai. Hanya admin. Memakai $linked_parent / $linked_children (+ $employee saat edit). --}}
@if (Auth::user()->hasRole('admin'))
    <x-section-form
        :label="__('label.also_parent')"
        icon="fa-solid fa-child"
    />

    @if ($linked_parent)
        <div class="alert alert-info">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <div><b>{{ $linked_parent->name }}</b> ({{ $linked_parent->phone ?: '-' }})</div>
                    <div class="mt-1">{{ __('label.my_children') }}:
                        @forelse ($linked_children as $child)
                            <span class="badge bg-light text-dark">{{ $child->name }} ({{ $child->nis }})</span>
                        @empty
                            -
                        @endforelse
                    </div>
                    <small class="d-block mt-2">{{ __('string.account_link_login_info') }}</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" id="btn-unlink-parent"
                    data-parent="{{ $linked_parent->encrypted_id }}">
                    {{ __('label.unlink') }}
                </button>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-md-6">
                <x-form.radio
                    name="also_parent"
                    :label="__('label.also_parent')"
                    :old="old('also_parent', '0')"
                    :option="$yesno"
                />
            </div>
        </div>

        <div id="also-parent-box" style="display:none" class="mb-3">
            <div id="selected-children" class="mb-2 d-flex flex-wrap gap-2"></div>
            @error('link_students')
                <div class="text-danger mb-2">{{ $message }}</div>
            @enderror
            <button type="button" class="btn btn-outline-primary" id="btn-pick-children">
                <i class="fa-solid fa-child"></i> {{ __('label.pick_children') }}
            </button>
        </div>

        <div class="modal fade" id="modal-children" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('label.pick_children') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">{{ __('string.pick_children_info') }}</p>
                        <div class="table-responsive">
                            <table id="table-children" class="table table-bordered table-hover w-100">
                                <thead>
                                    <tr>
                                        <th>{{ __('label.name') }}</th>
                                        <th>{{ __('label.nis') }}</th>
                                        <th>{{ __('label.class') }}</th>
                                        <th>{{ __('label.parent') }}</th>
                                        <th>{{ __('label.phone_number') }}</th>
                                        <th width="130" class="text-center">{{ __('label.action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <span class="me-auto text-muted" id="selected-count"></span>
                        <button type="button" class="btn btn-primary" id="btn-confirm-children">{{ __('label.done') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('styles')
        <link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    @endpush

    @push('scripts')
        <script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
        <script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}" type="text/javascript"></script>
        <script>
            $(document).ready(function() {
                const employeeId = "{{ isset($employee) ? $employee->encrypted_id : '' }}"
                const urlPreview = "{{ route('hr.account-link.preview') }}"
                const urlStudents = "{{ route('hr.account-link.students') }}"
                const urlChildren = "{{ route('hr.account-link.children') }}"
                const urlUnlink = "{{ route('hr.account-link.unlink') }}"
                const initial = @json($selected_students ?? []);
                const selected = new Map()
                let table = null
                const esc = (v) => htmlEntities(String(v ?? "-"))

                initial.forEach(st => selected.set(st.id, { name: st.name, nis: st.nis, parent: st.parent_key }))

                const toggleBox = () => $("#also-parent-box").toggle($("input[name=also_parent]:checked").val() == "1")
                $("input[name=also_parent]").on("change", toggleBox)
                toggleBox()

                // Nama-nama yang terpilih (di atas tombol modal), bisa dihapus; id-nya ikut terkirim saat form disimpan
                function renderChips() {
                    const $box = $("#selected-children").empty()

                    selected.forEach((st, id) => {
                        const $chip = $(`<span class="badge bg-primary-subtle text-primary fw-normal d-inline-flex align-items-center gap-1">
                            <span>${esc(st.name)}${st.nis ? " (" + esc(st.nis) + ")" : ""}</span>
                            <a href="javascript:void(0)" class="text-danger remove-child" data-id="${id}" title="{{ __('string.remove_parent_selection') }}"><i class="fa-solid fa-xmark"></i></a>
                            <input type="hidden" name="link_students[]" value="${id}">
                        </span>`)
                        $box.append($chip)
                    })

                    $("#selected-count").text(selected.size ? `${selected.size} {{ __('label.student') }}` : "")
                }

                function actionHtml(row) {
                    if (selected.has(row.encrypted_id))
                        return `<button type="button" class="btn btn-sm btn-success" disabled><i class="fa-solid fa-check"></i> {{ __('label.selected') }}</button>`

                    if (row.parent_has_account)
                        return `<span class="badge bg-light text-muted border fw-normal">{{ __('label.parent_has_account') }}</span>`

                    return `<button type="button" class="btn btn-sm btn-primary btn-pick-row" data-id="${row.encrypted_id}" data-name="${esc(row.name)}" data-nis="${esc(row.nis)}" data-parent="${row.parent_key}">{{ __('label.choose') }}</button>`
                }

                // Yang dihubungkan adalah data orang tuanya (semua anaknya ikut), jadi menghapus satu anak
                // menghapus seluruh pilihan orang tua tersebut
                $("#selected-children").on("click", ".remove-child", function() {
                    selected.clear()
                    renderChips()
                    if (table) table.rows().invalidate("data").draw(false)
                })

                $("#btn-pick-children").on("click", function() {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById("modal-children")).show()
                })

                $("#modal-children").on("shown.bs.modal", function() {
                    if (table) return

                    table = $("#table-children").DataTable({
                        language: { search: "", searchPlaceholder: `${label_search}...`, lengthMenu: "_MENU_ Data", emptyTable: label_nodata },
                        ajax: { url: urlStudents, type: "POST" },
                        processing: true,
                        serverSide: true,
                        ordering: false,
                        aLengthMenu: [[10, 25, 50], [10, 25, 50]],
                        columns: [
                            { render: (d, t, row) => htmlEntities(row.name) },
                            { render: (d, t, row) => htmlEntities(row.nis ?? "-") },
                            { render: (d, t, row) => htmlEntities(row.class) },
                            { render: (d, t, row) => row.parent_name ? htmlEntities(row.parent_name) : `<span class="text-muted">{{ __('label.no_parent_record') }}</span>` },
                            { render: (d, t, row) => row.parent_phone ? phoneFormat(row.parent_phone) : "-" },
                            { class: "text-center", render: (d, t, row) => actionHtml(row) },
                        ]
                    })
                })

                // Pilih -> status berubah menjadi "Terpilih"; seluruh anak dari orang tua yang sama ikut terpilih
                $("#table-children").on("click", ".btn-pick-row", function() {
                    const $btn = $(this)
                    const first = selected.values().next().value

                    if (first && first.parent !== $btn.data("parent"))
                        return Swal.fire("{{ __('label.pick_children') }}", "{{ __('message.account_link_students_one_parent') }}", "info")

                    $btn.prop("disabled", true)

                    $.post(urlChildren, { student: $btn.data("id") })
                        .done(function(res) {
                            res.data.children.forEach(c => selected.set(c.id, { name: c.name, nis: c.nis, parent: res.data.parent_key }))
                            renderChips()
                            table.rows().invalidate("data").draw(false)
                        })
                        .fail(function(xhr) {
                            $btn.prop("disabled", false)
                            Swal.fire("Error", (xhr.responseJSON && xhr.responseJSON.message) || "Error", "error")
                        })
                })

                // Selesai: tinjau penggabungan sebelum modal ditutup (penggabungan dilakukan saat form disimpan)
                $("#btn-confirm-children").on("click", function() {
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modal-children"))

                    if (selected.size === 0) return modal.hide()

                    $.post(urlPreview, { employee: employeeId, students: Array.from(selected.keys()) })
                        .done(function(res) {
                            const d = res.data
                            const children = d.children.map(c => `<li>${esc(c.name)} (${esc(c.nis)}) - ${esc(c.class)}</li>`).join("")
                            let html = `<div class="text-start"><p><b>${esc(d.parent.name)}</b> (${esc(d.parent.phone)})</p><ul>${children}</ul>`

                            if (d.new_parent)
                                html += `<p>{{ __('string.account_link_new_parent_info') }}</p>`

                            if (d.old_user)
                                html += `<p>Akun orang tua lama <b>${esc(d.old_user.name)}</b> akan dinonaktifkan; ${d.moved_rows} baris riwayat dipindahkan ke akun pegawai.</p>`

                            d.warnings.forEach(w => html += `<p class="text-warning mb-1">${esc(w)}</p>`)

                            if (!d.can_link) {
                                html += d.blockers.map(b => `<p class="text-danger mb-1">${esc(b.message)}</p>`).join("") + "</div>"
                                return Swal.fire({ icon: "error", title: "{{ __('label.cannot_link') }}", html: html })
                            }

                            html += `<p class="text-danger mt-2">{{ __('string.account_link_login_warning') }}</p><p class="text-muted mb-0">{{ __('string.account_link_saved_on_submit') }}</p></div>`

                            Swal.fire({
                                icon: "question", title: "{{ __('label.link_parent_title') }}", html: html,
                                showCancelButton: true, confirmButtonText: "{{ __('label.done') }}", cancelButtonText: "{{ __('label.cancel') }}"
                            }).then(function(r) {
                                if (r.isConfirmed) modal.hide()
                            })
                        })
                        .fail(function(xhr) {
                            Swal.fire("Error", (xhr.responseJSON && xhr.responseJSON.message) || "Error", "error")
                        })
                })

                renderChips()

                $("#btn-unlink-parent").on("click", function() {
                    const parent = $(this).data("parent")

                    Swal.fire({
                        icon: "warning", title: "{{ __('label.unlink') }}", text: "{{ __('string.account_unlink_warning') }}",
                        showCancelButton: true, confirmButtonText: "{{ __('label.unlink') }}", cancelButtonText: "{{ __('label.cancel') }}"
                    }).then(function(r) {
                        if (!r.isConfirmed) return

                        $.post(urlUnlink, { parent: parent })
                            .done(res => Swal.fire("OK", res.message, "success").then(() => location.reload()))
                            .fail(xhr => Swal.fire("Error", (xhr.responseJSON && xhr.responseJSON.message) || "Error", "error"))
                    })
                })
            })
        </script>
    @endpush
@endif
