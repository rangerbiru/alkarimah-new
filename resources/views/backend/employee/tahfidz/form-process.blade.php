<div>
    @csrf
    <input type="hidden" name="pertemuan" id="pertemuan" value="{{ $activeAbsensi->pluck('pertemuan')->last() }}">
    <input type="hidden" id="id_target" name="id_target">
    <input type="hidden" id="siswa-input" name="id_santri">
    <input type="hidden" id="id_surat">
    <input type="hidden" value="{{ $jenisKaldik }}" readonly id="jenis-kaldik">

    <div class="row">
        <div class="col-md-6">
            <x-form.select name="nama_santri" id="nama_santri" :label="__('label.student_name')" :option="$activeAbsensi->pluck('nama_santri', 'id_santri')" :placeholder="__('Pilih Nama Siswa')"
                required />
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <x-form.input-text name="target_pertemuan_ini" id="targetPertemuanIni" :label="__('label.current_target_ziyadah')" readonly />
        </div>
        <div class="col-md-6">
            <x-form.input-text name="jml_target" id="jmlTarget" :label="__('label.target_amount')" readonly />
        </div>
    </div>

    <x-section-form :label="__('label.last_meeting_ziyadah')" icon="bx bxs-book" />
    <div class="row">
        <div class="col-md-4">
            <x-form.input-text name="mulai_proses" id="mulaiProses" :label="__('label.last_meeting_target')" readonly />
            <input type="hidden" name="mulai_proses_juz" id="juzDropdown" class="form-control" placeholder="Juz Tes"
                readonly>
        </div>
        <div class="col-md-4">
            <input type="hidden" name="mulai_proses_halaman" id="halamanDropdown" class="form-control"
                placeholder="Halaman Tes" readonly>
        </div>
        @if ($jenisKaldik === 'Ziyadah' || $jenisKaldik === 'Murojaah Sabqi')
            <div class="col-md-4">
                <input type="hidden" name="mulai_proses_baris" id="barisDropdown" class="form-control"
                    placeholder="Baris Tes" readonly>
            </div>
        @endif

        <div class="col-md-4">
            <x-form.input-text name="ayat" id="processAyat" :label="__('label.last_ayat')" readonly />
        </div>
    </div>

    <x-section-form :label="__('label.today_ziyadah')" icon="bx bxs-book-bookmark" />
    <div class="row">
        <div class="col-md-4">
            <x-form.select name="capaian_target_juz" id="capaiJuzDropdown" :label="__('label.juz')" :option="$target->pluck('juz', 'juz')->mapWithKeys(function ($value, $key) {
                return [$key => 'Juz ' . $value];
            })"
                :placeholder="__('Pilih Juz')" />
        </div>
        <div class="col-md-4">
            <x-form.select name="capaian_target_halaman" id="capaiHalamanDropdown" :label="__('label.page')" :option="$halamanList ?? []"
                :placeholder="__('Pilih Halaman')" />
        </div>
        @if ($jenisKaldik === 'Ziyadah' || $jenisKaldik === 'Murojaah Sabqi')
            <div class="col-md-4">
                <x-form.select name="capaian_target_baris" id="capaiBarisDropdown" :label="__('label.line')" :option="$barisList ?? []"
                    :placeholder="__('Pilih Baris')" />
            </div>
        @endif

        <div class="col-md-4">
            <x-form.input-text name="ayat" id="capaianAyat" :label="__('label.last_ayat')" readonly />
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            @if ($jenisKaldik === 'Ziyadah' || $jenisKaldik === 'Murojaah Sabqi')
                <x-form.input-text name="capaian_target" id="capaianBaris" :label="__('label.line_achievement')" readonly />
            @else
                <x-form.input-text name="capaian_target" id="capaianBaris" :label="__('label.page_achievement')" readonly />
            @endif
        </div>
    </div>
</div>



@push('scripts')
    <script>
        $(document).ready(function() {
            const selectSantri = $('#nama_santri');
            const idSuratInput = $('#id_surat');
            const jenisKaldik = $('#jenis-kaldik').val();
            const processAyat = $('#processAyat');
            const capaianAyat = $('#capaianAyat');
            const mulaiProses = $('#mulaiProses');

            function resetInputs() {
                $('#jmlTarget').val('');
                $('#juzDropdown').val('');
                $('#halamanDropdown').val('');
                $('#barisDropdown').val('');
                $('#siswa-input').val('');
                $('#targetPertemuanIni').val('');
                $('#capaiJuzDropdown').val('');
                idSuratInput.val('');
                $('#nama_santri').val('');
            }

            const getIdSuratUrl = "{{ route('employee.tahfidz.process.get-id-surat') }}";
            const getIdSuratMurojaahUrl = "{{ route('employee.tahfidz.process.get-id-surat-murojaah') }}";

            if (jenisKaldik === "Ziyadah") {
                // Fungsi untuk fetch ID surat berdasarkan juz, halaman, dan baris
                function fetchIdSurat(juz, halaman, baris) {
                    if (juz && halaman && baris) {
                        $.get(`${getIdSuratUrl}?juz=${juz}&halaman=${halaman}&baris=${baris}`)
                            .done(function(data) {
                                processAyat.val(data.ayat);
                                idSuratInput.val(data.id);
                            })
                            .fail(function(error) {
                                console.error('Error fetching ID:', error);
                                idSuratInput.val('');
                            });
                    }
                }

                selectSantri.on('change', function() {
                    const idSantri = $(this).val();

                    const hari = $('#pertemuan').val();

                    if (idSantri) {
                        $.get(`./get-target-ziyadah/${idSantri}?hari=${hari}`)
                            .done(function(data) {

                                $('#jmlTarget').val(`${data.target_perhari || ''} Baris`);
                                $('#siswa-input').val(data.id_santri || '');
                                $('#targetPertemuanIni').val(data.target_ziyadah || '');
                                $('#capaiJuzDropdown').val('');
                                $('#id_target').val(data.id);

                                $('#juzDropdown').val(data.mulai_proses_juz || data.mulai_target_juz);
                                $('#halamanDropdown').val(data.mulai_proses_halaman || data
                                    .mulai_target_halaman);
                                $('#barisDropdown').val(data.mulai_proses_baris || data
                                    .mulai_target_baris);

                                $('#mulaiProses').val('Juz ' + data
                                    .mulai_target_juz + ' Halaman ' + data.mulai_target_halaman +
                                    ' Baris ke ' + data
                                    .mulai_target_baris)

                                fetchIdSurat(
                                    $('#juzDropdown').val(),
                                    $('#halamanDropdown').val(),
                                    $('#barisDropdown').val()
                                );
                            })
                            .fail(function(error) {
                                console.error('Error fetching target_ziyadah:', error);
                                alert('Data santri tidak tersedia di pertemuan sebelumnya.');
                                resetInputs();
                            });
                    } else {
                        resetInputs();
                    }
                });
            } else if (jenisKaldik === "Murojaah Manzil") {
                function fetchIdSuratMurojaah(juz, halaman) {
                    if (juz && halaman) {
                        $.get(`${getIdSuratMurojaahUrl}?juz=${juz}&halaman=${halaman}`)
                            .done(function(data) {
                                processAyat.val(data.ayat);
                                idSuratInput.val(data.halaman);
                            })
                            .fail(function(error) {
                                console.error('Error fetching ID:', error);
                                idSuratInput.val('');
                            });
                    }
                }

                selectSantri.on('change', function() {
                    const idSantri = $(this).val();
                    const hari = $('#pertemuan').val();

                    if (idSantri) {
                        $.get(`./get-target-manzil/${idSantri}?hari=${hari}`)
                            .done(function(data) {
                                $('#jmlTarget').val(`${data.target_perhari || ''} Halaman`);
                                $('#siswa-input').val(data.id_santri || '');
                                $('#targetPertemuanIni').val(data.target_murojaah || '');
                                $('#capaiJuzDropdown').val('');
                                $('#id_target').val(data.id);

                                $('#juzDropdown').val(data.mulai_proses_juz || data.mulai_target_juz);
                                $('#halamanDropdown').val(data.mulai_proses_halaman || data
                                    .mulai_target_halaman);

                                $('#mulaiProses').val('Juz ' + data
                                    .mulai_target_juz + ' Halaman ' + data.mulai_target_halaman)

                                fetchIdSuratMurojaah(
                                    $('#juzDropdown').val(),
                                    $('#halamanDropdown').val()
                                );
                            })
                            .fail(function(error) {
                                console.error('Error fetching target_murojaah:', error);
                                alert('Data santri tidak tersedia di pertemuan sebelumnya.');
                                resetInputs();
                            });
                    } else {
                        resetInputs();
                    }
                });
            } else if (jenisKaldik === "Murojaah Sabqi") {
                function fetchIdSurat(juz, halaman, baris) {
                    if (juz && halaman && baris) {
                        $.get(`${getIdSuratUrl}?juz=${juz}&halaman=${halaman}&baris=${baris}`)
                            .done(function(data) {
                                processAyat.val(data.ayat);
                                idSuratInput.val(data.id);
                            })
                            .fail(function(error) {
                                console.error('Error fetching ID:', error);
                                idSuratInput.val('');
                            });
                    }
                }

                selectSantri.on('change', function() {
                    const idSantri = $(this).val();
                    const hari = $('#pertemuan').val();

                    if (idSantri) {
                        $.get(`./get-target-sabqi/${idSantri}?hari=${hari}`)
                            .done(function(data) {
                                $('#jmlTarget').val(`${data.target_perhari || ''} Baris`);
                                $('#siswa-input').val(data.id_santri || '');
                                $('#targetPertemuanIni').val(data.target_murojaah || '');
                                $('#capaiJuzDropdown').val('');
                                $('#id_target').val(data.id);

                                $('#juzDropdown').val(data.mulai_proses_juz || data.mulai_target_juz);
                                $('#halamanDropdown').val(data.mulai_proses_halaman || data
                                    .mulai_target_halaman);
                                $('#barisDropdown').val(data.mulai_proses_baris || data
                                    .mulai_target_baris);

                                $('#mulaiProses').val('Juz ' + data
                                    .mulai_target_juz + ' Halaman ' + data.mulai_target_halaman +
                                    ' Baris ke ' + data
                                    .mulai_target_baris)

                                fetchIdSurat(
                                    $('#juzDropdown').val(),
                                    $('#halamanDropdown').val(),
                                    $('#barisDropdown').val()
                                );
                            })
                            .fail(function(error) {
                                console.error('Error fetching target_murojaah:', error);
                                alert('Data santri tidak tersedia di pertemuan sebelumnya.');
                                resetInputs();
                            });
                    } else {
                        resetInputs();
                    }
                });
            }

            // Dropdown interactions
            const juzDropdown = $('#capaiJuzDropdown');
            const halamanDropdown = $('#capaiHalamanDropdown');
            const barisDropdown = $('#capaiBarisDropdown');

            if (jenisKaldik === "Ziyadah" || jenisKaldik === "Murojaah Sabqi") {
                function fetchIdSurat(juz, halaman, baris) {
                    if (juz && halaman && baris) {
                        $.get(`${getIdSuratUrl}?juz=${juz}&halaman=${halaman}&baris=${baris}`)
                            .done(function(data) {
                                capaianAyat.val(data.ayat);
                                const capaianBaris = $('#capaianBaris');
                                const id1 = parseInt(data.id) || 0;
                                const id2 = parseInt(idSuratInput.val()) || 0;

                                if (data.id >= 8288) {
                                    if (id1 > id2) {
                                        alert("Afwan, tidak boleh kurang dari ziyadah sebelumnya");
                                        capaianBaris.val('');
                                        return;
                                    }
                                }
                                capaianBaris.val(`${Math.abs(id1 - id2) + 1}`);

                                if (capaianBaris.val() == 0) {
                                    alert("Afwan, tidak boleh kurang dari ziyadah sebelumnya");
                                    capaianBaris.val('');
                                }
                            })
                            .fail(function(error) {
                                console.error('Error fetching ID Surat:', error);
                                $('#capaianBaris').val('');
                            });
                    }
                }

                juzDropdown.on('change', function() {
                    let juz = $(this).val();
                    halamanDropdown.html('<option value="">Pilih Halaman</option>');
                    barisDropdown.html('<option value="">Pilih Baris</option>');

                    if (juz) {
                        $.get(`./get-halaman/${juz}`)
                            .done(function(data) {
                                $.each(data, function(index, item) {
                                    halamanDropdown.append($('<option>', {
                                        value: item.halaman,
                                        text: `Halaman ${item.halaman}`
                                    }));
                                });
                            })
                            .fail(function(error) {
                                console.error('Error fetching halaman:', error);
                            });
                    }
                });

                halamanDropdown.on('change', function() {
                    let halaman = $(this).val();
                    barisDropdown.html('<option value="">Pilih Baris</option>');

                    if (halaman) {
                        $.get(`./get-baris/${halaman}`)
                            .done(function(data) {
                                $.each(data, function(key, value) {
                                    barisDropdown.append($('<option>', {
                                        value: key,
                                        text: `Baris ke ${value}`
                                    }));
                                });
                            })
                            .fail(function(error) {
                                console.error('Error fetching baris:', error);
                            });
                    }
                });

                barisDropdown.on('change', function() {
                    fetchIdSurat(juzDropdown.val(), halamanDropdown.val(), $(this).val());
                });
            } else if (jenisKaldik === "Murojaah Manzil") {
                function fetchIdSuratMurojaah(juz, halaman) {
                    if (juz && halaman) {
                        $.get(`${getIdSuratMurojaahUrl}?juz=${juz}&halaman=${halaman}`)
                            .done(function(data) {
                                capaianAyat.val(data.ayat)
                                const capaianBaris = $('#capaianBaris');
                                const id1 = parseInt(data.halaman);
                                const id2 = parseInt(idSuratInput.val()) || 0;
                                capaianBaris.val(`${Math.abs(id1 - id2) + 1}`);
                            })
                            .fail(function(error) {
                                console.error('Error fetching ID Surat:', error);
                                $('#capaianBaris').val('');
                            });
                    }
                }

                juzDropdown.on('change', function() {
                    let juz = $(this).val();
                    halamanDropdown.html('<option value="">Pilih Halaman</option>');

                    if (juz) {
                        $.get(`./get-halaman/${juz}`)
                            .done(function(data) {

                                $.each(data, function(index, item) {
                                    halamanDropdown.append($('<option>', {
                                        value: item.halaman,
                                        text: `Halaman ${item.halaman}`
                                    }));
                                });
                            })
                            .fail(function(error) {
                                console.error('Error fetching halaman:', error);
                            });
                    }
                });

                halamanDropdown.on('change', function() {
                    fetchIdSuratMurojaah(juzDropdown.val(), $(this).val());
                });
            }
        });
    </script>

    {{-- <script>
        $('#nama_santri').on('change', function() {
            var value = $(this).val();
            console.log(value);

        });
    </script> --}}
@endpush
