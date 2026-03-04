@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/hafalan/create" />
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <form method="post" action="{{ route('employee.hafalan.store') }}" class="form-block">
                <div class="card">
                    <div class="card-body">
                        @csrf
                        {{-- <input type="hidden" name="id_halaqoh" id="id_halaqoh"> --}}
                        <input type="hidden" name="id_pengampu" value="{{ $dataHalaqoh->pluck('id_pegawai')->last() }}">

                        <div class="row">
                            <div class="col-md-10">
                                <label for="nama_kaldik">{{ __('label.kaldik_name') }}</label>
                                <select id="nama_kaldik" name="nama_kaldik" class="form-control select2">
                                    <option value="#" disabled selected>Pilih Kaldik</option>
                                    @foreach ($dataKaldik as $kaldik)
                                        <option value="{{ $kaldik->nama_kaldik }}">{{ $kaldik->nama_kaldik }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-10">
                                <x-form.input-text name="periode_kaldik" id="periode_kaldik" :label="'Periode Kaldik'"
                                    :old="old('periode_kaldik')" readonly />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-10">
                                <x-form.input-text name="nama_lembaga" id="nama_lembaga" :label="__('label.lembaga_name')" :old="'Ponpes Ibnu Abbas'"
                                    readonly />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-10">
                                <x-form.input-text name="jenis_kaldik" id="jenis_kaldik" :label="'Jenis Kaldik'" :old="old('jenis_kaldik')"
                                    readonly />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-10">
                                <x-form.input-text name="nama_pengajar" id="nama_pengajar" :label="__('label.teacher_name')"
                                    :old="$dataHalaqoh->pluck('nama_pengampu')->last()" readonly />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-10">
                                <x-form.input-text name="nama_halaqoh" id="nama_halaqoh" :label="__('label.halaqoh_name')"
                                    :old="'Ustadz ' . $dataHalaqoh->pluck('nama_pengampu')->last()" readonly />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-10">
                                <label for="nama_santri">{{ __('label.student_name') }}</label>
                                <select id="nama_santri" name="nama_santri"
                                    class="form-control form-control-lg form-select select2">
                                    <option value="#" disabled selected>Pilih Nama Siswa</option>
                                    @foreach ($namaSantri as $santri)
                                        <option value="{{ $santri->name }}" data-id="{{ $santri->id }}"
                                            {{ old('nama_santri') == $santri->name ? 'selected' : '' }}>
                                            {{ $santri->name }} | {{ $santri->nis }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Input Id Santri --}}
                        <input type="hidden" id="id-input" name="id_santri">

                        <div class="row my-3">
                            <div class="col-md-10">
                                <x-form.select name="jenis_target" :option="['Baris', 'Halaman']" id="jenis_target" :label="__('label.target_type')"
                                    :old="old('jenis_target')" />
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-md-10">
                                <x-form.input-text name="target_perhari" id="target_perhari" type="number"
                                    :label="__('label.target_amount_perday')" :old="old('target_perhari')" min="1" />
                            </div>
                        </div>

                        <div id="form-ziyadah">
                            <x-section-form :label="__('label.target_start')" icon="bx bxs-graduation" />
                            <div class="row">
                                <div class="col-md-4">
                                    <x-form.select name="mulai_target_juz" id="juzDropdown" :label="__('label.juz')"
                                        :option="$target->pluck('juz', 'juz')->unique()->toArray()" :old="old('juz')" />
                                </div>
                                <div class="col-md-4">
                                    <x-form.select name="mulai_target_halaman" id="pageDropdown" :label="__('label.page')"
                                        :option="[]" :old="old('page')" />
                                </div>
                                <div class="col-md-4" id="barisGroup">
                                    <x-form.select name="mulai_target_baris" id="lineDropdown" :label="__('label.line')"
                                        :option="[]" :old="old('line')" />
                                </div>
                            </div>
                        </div>


                        {{-- Target Start dan End (untuk Murojaah) --}}
                        <div id="form-murojaah" style="display: none;">
                            <x-section-form :label="__('label.target_end')" icon="bx bxs-graduation" />
                            <div class="row">
                                <div class="col-md-4">
                                    <x-form.select name="akhir_target_juz" id="endJuzDropdown" :label="__('label.juz')"
                                        :option="$target->pluck('juz', 'juz')->unique()->toArray()" :old="old('juz')" />
                                </div>
                                <div class="col-md-4">
                                    <x-form.select name="akhir_target_halaman" id="endPageDropdown" :label="__('label.page')"
                                        :option="[]" :old="old('page')" />
                                </div>
                                <div class="col-md-4" id="endBarisGroup">
                                    <x-form.select name="akhir_target_baris" id="endLineDropdown" :label="__('label.line')"
                                        :option="[]" :old="old('line')" />
                                </div>
                            </div>
                        </div>



                        <x-form.button-submit :cancel-route="route('employee.hafalan.index')" />
                    </div>
                </div>
            </form>


        </div>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#juzDropdown').on('change', function() {
                let juz = $(this).val();


                // Hapus semua opsi halaman sebelumnya
                let $pageDropdown = $('#pageDropdown');
                $pageDropdown.html('<option value="">Pilih Halaman</option>');

                let $lineDropdown = $('#lineDropdown');
                $lineDropdown.html('<option value="">Pilih Baris</option>');

                if (juz) {
                    $.ajax({
                        url: `get-halaman/${juz}`,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            $.each(data, function(key, value) {
                                $pageDropdown.append(
                                    $('<option>', {
                                        value: key,
                                        text: value
                                    })
                                );
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching halaman:', error);
                        }
                    });
                }
            });

            $('#pageDropdown').on('change', function() {
                let halaman = $(this).val();

                let $lineDropdown = $('#lineDropdown');
                $lineDropdown.html('<option value="">Pilih Baris</option>');

                if (halaman) {
                    $.ajax({
                        url: `get-baris/${halaman}`,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            $.each(data, function(key, value) {
                                $lineDropdown.append(
                                    $('<option>', {
                                        value: key,
                                        text: value
                                    })
                                );
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching baris:', error);
                        }
                    });
                }
            });

            $('#endJuzDropdown').on('change', function() {
                let juz = $(this).val();


                // Hapus semua opsi halaman sebelumnya
                let $endPageDropdown = $('#endPageDropdown');
                $endPageDropdown.html('<option value="">Pilih Halaman</option>');

                let $endLineDropdown = $('#endLineDropdown');
                $endLineDropdown.html('<option value="">Pilih Baris</option>');

                if (juz) {
                    $.ajax({
                        url: `get-halaman/${juz}`,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            $.each(data, function(key, value) {
                                $endPageDropdown.append(
                                    $('<option>', {
                                        value: key,
                                        text: value
                                    })
                                );
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching halaman:', error);
                        }
                    });
                }
            });

            $('#endPageDropdown').on('change', function() {
                let halaman = $(this).val();

                let $endLineDropdown = $('#endLineDropdown');
                $endLineDropdown.html('<option value="">Pilih Baris</option>');

                if (halaman) {
                    $.ajax({
                        url: `get-baris/${halaman}`,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            $.each(data, function(key, value) {
                                $endLineDropdown.append(
                                    $('<option>', {
                                        value: key,
                                        text: value
                                    })
                                );
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching baris:', error);
                        }
                    });
                }
            });
        });
    </script>


    <script>
        const kaldikData = @json($dataKaldik);

        function toggleFormJenisKaldik() {
            const jenis = $('#jenis_kaldik').val();

            if (jenis === "Ziyadah") {
                $('#form-ziyadah').show();
                $('#form-murojaah').hide();
                $('#barisGroup').show();
            } else if (jenis === "Murojaah Sabqi") {
                $('#form-ziyadah').show();
                $('#form-murojaah').show();
                $('#barisGroup').show();
                $('#endBarisGroup').show();
            } else if (jenis === "Murojaah Manzil") {
                $('#form-ziyadah').show();
                $('#form-murojaah').show();
                $('#barisGroup').hide();
                $('#endBarisGroup').hide();
            } else {
                $('#form-ziyadah').hide();
                $('#form-murojaah').hide();
            }
        }

        $(document).ready(function() {
            $('#nama_kaldik').select2({
                placeholder: "Pilih Kaldik",
                allowClear: true
            });

            $('#nama_santri').select2({
                placeholder: "Pilih Nama Santri",
                allowClear: true
            });

            $('#nama_santri').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const idSantri = selectedOption.attr('data-id')

                const jenisKaldik = $('#jenis_kaldik').val();
                $('#id-input').val(idSantri);

                if (idSantri && jenisKaldik) {
                    $.ajax({
                        url: "{{ route('employee.hafalan.checkStudent') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id_santri: idSantri,
                            jenis_kaldik: jenisKaldik
                        },
                        success: function(response) {
                            if (response.exists) {
                                Swal.fire({
                                    title: 'Sudah Terdaftar',
                                    text: response.message,
                                    icon: 'warning'
                                });
                                $('#nama_santri').val('').trigger('change');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Gagal mengecek data santri.', 'error');
                        }
                    })
                }


            });

            $('#nama_kaldik').on('change', function() {
                const selectedNamaKaldik = $(this).val();
                const selectedData = kaldikData.find(k => k.nama_kaldik === selectedNamaKaldik);

                $('#juzDropdown').val('').trigger('change');
                $('#pageDropdown').html('<option value="">Pilih Halaman</option>');
                $('#lineDropdown').html('<option value="">Pilih Baris</option>');

                $('#endJuzDropdown').val('').trigger('change');
                $('#endPageDropdown').html('<option value="">Pilih Halaman</option>');
                $('#endLineDropdown').html('<option value="">Pilih Baris</option>');

                if (selectedData) {
                    $('#nama_lembaga').val(selectedData.nama_lembaga);
                    $('#periode_kaldik').val(selectedData.periode_kaldik);
                    $('#jenis_kaldik').val(selectedData.jenis_kaldik);

                    toggleFormJenisKaldik();
                } else {
                    $('#nama_lembaga').val('');
                    $('#periode_kaldik').val('');
                    $('#jenis_kaldik').val('');
                }

                function updateJenisTargetOptions(jenisKaldik) {
                    const $jenisTarget = $('#jenis_target');
                    $jenisTarget.empty(); // Hapus semua option

                    if (jenisKaldik === 'Ziyadah') {
                        $jenisTarget.append(new Option('Baris', 'Baris'));
                        $jenisTarget.append(new Option('Halaman', 'Halaman'));
                    } else if (jenisKaldik === 'Murojaah Sabqi') {
                        $jenisTarget.append(new Option('Baris', 'Baris'));
                    } else if (jenisKaldik === 'Murojaah Manzil') {
                        $jenisTarget.append(new Option('Halaman', 'Halaman'));
                    } else {
                        // default kosong atau disabled
                        $jenisTarget.append(new Option('Pilih Jenis Target', '', true, true)).prop(
                            'disabled', true);
                    }
                }

                updateJenisTargetOptions(selectedData.jenis_kaldik);

            });

            toggleFormJenisKaldik();
        });
    </script>

@endsection
