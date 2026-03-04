<!-- Modal Detail Pelanggaran (3 Sections) -->
<div class="modal fade" id="detailViolationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bx bx-info-circle me-2"></i>Detail Pelanggaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light-subtle">

                <!-- SECTION 1: INFORMASI UMUM & SANTRI -->
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-bold text-primary"><i class="bx bx-user me-2"></i>Data Santri & Kejadian</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Baris 1: Tanggal & Petugas -->
                            <div class="col-md-6">
                                <small class="text-muted d-block text-uppercase" style="font-size: 0.75rem;">Tanggal &
                                    Waktu</small>
                                <span class="fw-semibold" id="modal-date-time">-</span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block text-uppercase" style="font-size: 0.75rem;">Petugas
                                    Pencatat</small>
                                <span class="fw-semibold" id="modal-reporter">-</span>
                            </div>

                            <div class="col-12">
                                <hr class="my-2 opacity-25">
                            </div>

                            <!-- Baris 2: Data Santri -->
                            <div class="col-md-3">
                                <small class="text-muted d-block text-uppercase" style="font-size: 0.75rem;">Nama
                                    Santri</small>
                                <h6 class="fw-bold mb-0 text-dark" id="modal-student-name">-</h6>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block text-uppercase" style="font-size: 0.75rem;">NIS</small>
                                <span class="fw-medium" id="modal-student-nis">-</span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block text-uppercase"
                                    style="font-size: 0.75rem;">Kelas</small>
                                <span class="fw-medium" id="modal-student-class">-</span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block text-uppercase"
                                    style="font-size: 0.75rem;">Lokasi</small>
                                <span id="modal-location">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: DETAIL PELANGGARAN -->
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-bold text-danger"><i class="bx bx-error me-2"></i>Detail Pelanggaran</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light border mb-3">
                            <p class="mb-0 fw-bold text-dark" id="modal-violation-desc">-</p>
                        </div>

                        <div class="row g-3 text-center">
                            <div class="col-md-4">
                                <div class="p-2 bg-light rounded">
                                    <small class="text-muted d-block text-uppercase"
                                        style="font-size: 0.7rem;">Kelompok</small>
                                    <span class="fs-5 fw-bold text-dark" id="modal-violation-group">-</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 bg-light rounded">
                                    <small class="text-muted d-block text-uppercase"
                                        style="font-size: 0.7rem;">Dampak</small>
                                    <span class="badge rounded-pill px-3 py-1 mt-1" id="modal-impact-badge">-</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 bg-light rounded">
                                    <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Poin
                                        Pelanggaran</small>
                                    <span class="fs-5 fw-bold text-danger" id="modal-points">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: CATATAN, BUKTI & STATUS -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-bold text-secondary"><i class="bx bx-detail me-2"></i>Catatan & Bukti</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Catatan -->
                            <div class="col-md-6">
                                <small class="text-muted d-block text-uppercase mb-1"
                                    style="font-size: 0.75rem;">Catatan Petugas</small>
                                <div class="bg-white border rounded p-3 mb-3"
                                    style=" white-space: pre-wrap; font-size: 0.9rem;" id="modal-notes">-</div>
                            </div>

                            <!-- Bukti Foto -->
                            <div class="col-md-6">
                                <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.75rem;">Bukti
                                    Foto</small>
                                <div class="bg-white border rounded d-flex align-items-center justify-content-center overflow-hidden"
                                    style="min-height: 120px; position: relative;" id="modal-proof-container">
                                    <span class="text-muted small"><i class="bx bx-image-alt me-1"></i>Tidak ada bukti
                                        foto</span>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-12 text-start mt-3">
                                <small class="text-muted me-2">Status Verifikasi:</small>
                                <span class="badge px-3 py-2" id="modal-status-badge">-</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="modal-btn-edit" class="btn btn-primary"><i class="bx bx-pencil me-1"></i>Edit
                    Data</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            // ... inisialisasi DataTables Anda ...

            // TAMBAHKAN EVENT LISTENER INI DI DALAM DOCUMENT READY
            // Delegasi event untuk tombol dinamis di dalam tabel
            $('#table-violation').on('click', '.btn-detail-violation', function() {
                let rowDataEncoded = $(this).data('row');
                let row = JSON.parse(decodeURIComponent(rowDataEncoded));

                showViolationDetail(row);
            });
        });

        // Fungsi Modal (Diperbaiki akses datanya)
        function showViolationDetail(row) {
            // 1. Isi Tanggal & Waktu
            // Pastikan row.date dan row.time ada
            $('#modal-date-time').text(`${row.date} pukul ${row.time}`);

            // Akses employee.name sesuai struktur controller baru
            $('#modal-reporter').text(row.employee ? row.employee.name : '-');

            // 2. Isi Data Santri (Akses nested object row.student)
            $('#modal-student-name').text(row.student ? row.student.name : '-');
            $('#modal-student-nis').text(`NIS: ${row.student ? row.student.nis : '-'}`);
            $('#modal-student-class').text(row.student ? row.student.class.name : '-');

            // 3. Isi Lokasi
            $('#modal-location').text(capitalizeFirstLetter(row.location));

            // 4. Isi Data Pelanggaran (Akses nested object row.violation)
            if (row.violation) {
                $('#modal-violation-desc').text(row.violation.description);
                $('#modal-violation-group').text(row.violation.group);
                $('#modal-points').text(`${row.violation.points} Poin`);

                // Handle Badge Dampak
                let impact = row.violation.impact_level;
                let impactClass = 'bg-secondary';

                if (impact === 'rendah') impactClass = 'bg-info text-white';
                else if (impact === 'menengah') impactClass = 'bg-warning text-white';
                else if (impact === 'tinggi') impactClass = 'bg-orange text-white';
                else if (impact === 'sangat tinggi') impactClass = 'bg-danger-subtle text-danger border border-danger';
                else if (impact === 'fatal') impactClass = 'bg-danger text-white';

                $('#modal-impact-badge').removeClass().addClass(`badge rounded-pill px-3 py-2 ${impactClass}`).text(impact);
            } else {
                $('#modal-violation-desc').text('-');
            }

            // 5. Isi Catatan
            $('#modal-notes').text(row.notes || 'Tidak ada catatan tambahan.');

            // 6. Handle Bukti Foto
            let proofContainer = $('#modal-proof-container');
            proofContainer.empty();

            if (row.proof) {
                let imageUrl = `/storage/${row.proof}`;
                proofContainer.html(`
                <img src="${imageUrl}" alt="Bukti" class="img-fluid rounded" style="max-height: 200px; cursor:pointer;" onclick="window.open(this.src)">
                <div class="mt-2"><small class="text-muted">Klik untuk perbesar</small></div>
            `);
            } else {
                proofContainer.html(
                    '<span class="text-muted"><i class="bx bx-image-alt display-4"></i><br>Tidak ada bukti foto</span>');
            }

            // 7. Handle Status
            let status = row.status;
            let statusClass = 'bg-secondary';

            if (status === 'draft') statusClass = 'bg-secondary';
            else if (status === 'tabayyun') statusClass = 'bg-warning text-dark';
            else if (status === 'disahkan') statusClass = 'bg-success';

            $('#modal-status-badge').removeClass().addClass(`badge px-3 py-2 ${statusClass}`).text((status || '-')
                .toUpperCase());

            // 8. Set Link Edit
            let editUrl = "{{ route('academic.violation.edit', ':id') }}".replace(':id', row.id);
            $('#modal-btn-edit').attr('href', editUrl);

            // 9. Tampilkan Modal
            // Gunakan jQuery untuk memicu modal Bootstrap 5
            const myModal = new bootstrap.Modal(document.getElementById('detailViolationModal'));
            myModal.show();
        }

        function capitalizeFirstLetter(string) {
            if (!string) return '';
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
    </script>
@endpush
