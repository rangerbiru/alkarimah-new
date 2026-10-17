{{-- "Gabungkan dengan akun pegawai" di form edit orang tua. Hanya admin. Memakai $parent dan $linked_employee. --}}
@if (Auth::user()->hasRole('admin'))
    <x-section-form
        :label="__('label.link_to_employee')"
        icon="fa-solid fa-link"
    />

    @if ($linked_employee)
        <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div>{{ __('string.parent_linked_to_employee') }}: <b>{{ $linked_employee->name }}</b> ({{ $linked_employee->nip ?: '-' }})</div>
                <small>{{ __('string.account_link_login_info') }}</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btn-unlink-employee">{{ __('label.unlink') }}</button>
        </div>
    @else
        <div class="row mb-3">
            <div class="col-md-8">
                <div id="employee-suggestion" class="alert alert-warning py-2" style="display:none"></div>
                <label class="form-label">{{ __('label.employee') }}</label>
                <select id="employee-link-select" class="form-select" style="width:100%"></select>
                <small class="text-muted">{{ __('string.link_employee_info') }}</small>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="button" class="btn btn-primary" id="btn-link-employee">{{ __('label.link') }}</button>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            $(document).ready(function() {
                const parentId = "{{ $parent->encrypted_id }}"
                const urlEmployees = "{{ route('hr.account-link.employees') }}"
                const urlPreview = "{{ route('hr.account-link.preview') }}"
                const urlLink = "{{ route('hr.account-link.link') }}"
                const urlUnlink = "{{ route('hr.account-link.unlink') }}"
                const esc = (v) => htmlEntities(String(v ?? "-"))
                const fail = (xhr) => Swal.fire("Error", (xhr.responseJSON && xhr.responseJSON.message) || "Error", "error")

                const $select = $("#employee-link-select")

                if ($select.length) {
                    $select.select2({
                        placeholder: "{{ __('string.link_employee_placeholder') }}",
                        width: "100%",
                        minimumInputLength: 2,
                        ajax: {
                            url: urlEmployees,
                            delay: 250,
                            data: (params) => ({ term: params.term, parent: parentId }),
                            processResults: (res) => ({
                                results: res.data.map(e => ({ id: e.id, text: e.label + (e.suggested ? " ★" : "") }))
                            })
                        }
                    })

                    // Saran: pegawai dengan nomor HP yang sama (tetap harus dipilih dan dikonfirmasi admin)
                    $.get(urlEmployees, { parent: parentId }).done(function(res) {
                        if (!res.data.length) return

                        const links = res.data.map(e => `<a href="#" class="suggest-employee" data-id="${e.id}" data-label="${esc(e.label)}">${esc(e.label)}</a>`).join(", ")
                        $("#employee-suggestion").html("{{ __('string.link_employee_suggestion') }}: " + links).show()
                    })

                    $("#employee-suggestion").on("click", ".suggest-employee", function(e) {
                        e.preventDefault()
                        $select.append(new Option($(this).data("label"), $(this).data("id"), true, true)).trigger("change")
                    })
                }

                $("#btn-link-employee").on("click", function() {
                    const employee = $select.val()

                    if (!employee) return Swal.fire("{{ __('label.employee') }}", "{{ __('string.link_employee_placeholder') }}", "info")

                    $.post(urlPreview, { employee: employee, parent: parentId }).done(function(res) {
                        const d = res.data
                        const children = d.children.map(c => `<li>${esc(c.name)} (${esc(c.nis)}) - ${esc(c.class)}</li>`).join("") || "<li>-</li>"
                        let html = `<div class="text-start">
                            <p><b>{{ __('label.employee') }}:</b> ${esc(d.employee.name)} (${esc(d.employee.nip)}) - ${esc(d.employee.phone)}</p>
                            <p><b>{{ __('label.parent') }}:</b> ${esc(d.parent.name)} (${esc(d.parent.phone)})</p>
                            <ul>${children}</ul>`

                        if (d.old_user)
                            html += `<p>Akun orang tua lama <b>${esc(d.old_user.name)}</b> (${esc(d.old_user.email)}) akan dinonaktifkan; ${d.moved_rows} baris riwayat dipindahkan.</p>`

                        d.warnings.forEach(w => html += `<p class="text-warning mb-1">${esc(w)}</p>`)

                        if (!d.can_link) {
                            html += d.blockers.map(b => `<p class="text-danger mb-1">${esc(b.message)}</p>`).join("") + "</div>"
                            return Swal.fire({ icon: "error", title: "{{ __('label.cannot_link') }}", html: html })
                        }

                        html += `<p class="text-danger mt-2">{{ __('string.account_link_login_warning') }}</p></div>`

                        Swal.fire({
                            icon: "question", title: "{{ __('label.link') }}", html: html,
                            showCancelButton: true, confirmButtonText: "{{ __('label.link') }}", cancelButtonText: "{{ __('label.cancel') }}"
                        }).then(function(r) {
                            if (!r.isConfirmed) return

                            $.post(urlLink, { employee: employee, parent: parentId })
                                .done(res => Swal.fire("OK", res.message, "success").then(() => location.reload()))
                                .fail(fail)
                        })
                    }).fail(fail)
                })

                $("#btn-unlink-employee").on("click", function() {
                    Swal.fire({
                        icon: "warning", title: "{{ __('label.unlink') }}", text: "{{ __('string.account_unlink_warning') }}",
                        showCancelButton: true, confirmButtonText: "{{ __('label.unlink') }}", cancelButtonText: "{{ __('label.cancel') }}"
                    }).then(function(r) {
                        if (!r.isConfirmed) return

                        $.post(urlUnlink, { parent: parentId })
                            .done(res => Swal.fire("OK", res.message, "success").then(() => location.reload()))
                            .fail(fail)
                    })
                })
            })
        </script>
    @endpush
@endif
