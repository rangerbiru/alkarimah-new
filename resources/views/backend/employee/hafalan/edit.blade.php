@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" :breadcrumb="'employee/hafalan'" />
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <form method="POST" action="{{ route('employee.hafalan.update', $hafalan->id) }}" class="form-block">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-body">
                        <input type="hidden" name="id_pengampu" value="{{ $hafalan->id_pengampu }}">

                        <div class="row">
                            <div class="col-md-10">
                                <label for="nama_kaldik">{{ __('label.kaldik_name') }}</label>
                                <select id="nama_kaldik" name="nama_kaldik" class="form-control select2">
                                    @foreach ($dataKaldik as $kaldik)
                                        <option value="{{ $kaldik->nama_kaldik }}"
                                            {{ $hafalan->nama_kaldik == $kaldik->nama_kaldik ? 'selected' : '' }}>
                                            {{ $kaldik->nama_kaldik }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-10">
                                <x-form.input-text name="periode_kaldik" id="periode_kaldik" :label="'Periode Kaldik'"
                                    :value="$hafalan->periode_kaldik" readonly />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-10">
                                <x-form.input-text name="nama_lembaga" id="nama_lembaga" :label="__('label.lembaga_name')" :value="$hafalan->nama_lembaga"
                                    readonly />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-10">
                                <x-form.input-text name="jenis_kaldik" id="jenis_kaldik" :label="'Jenis Kaldik'" :value="$hafalan->jenis_kaldik"
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

                        <input type="hidden" name="id_santri" value="{{ $hafalan->id_santri }}">

                        <div class="row my-3">
                            <div class="col-md-10">
                                <x-form.select name="jenis_target" :option="['Baris', 'Halaman']" id="jenis_target" :label="__('label.target_type')"
                                    :selected="$hafalan->jenis_target" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-10">
                                <x-form.input-text name="target_perhari" id="target_perhari" type="number"
                                    :label="__('label.target_amount_perday')" :value="$hafalan->target_perhari" />
                            </div>
                        </div>

                        {{-- START TARGET --}}
                        <div id="form-ziyadah">
                            <x-section-form :label="__('label.target_start')" icon="bx bxs-graduation" />
                            <div class="row">
                                <div class="col-md-4">
                                    <x-form.select name="mulai_target_juz" id="juzDropdown" :label="__('label.juz')"
                                        :option="$target->pluck('juz', 'juz')->unique()->toArray()" :selected="$hafalan->mulai_target_juz" />
                                </div>
                                <div class="col-md-4">
                                    <x-form.select name="mulai_target_halaman" id="pageDropdown" :label="__('label.page')"
                                        :option="[$hafalan->mulai_target_halaman => $hafalan->mulai_target_halaman]" :selected="$hafalan->mulai_target_halaman" />
                                </div>
                                <div class="col-md-4" id="barisGroup">
                                    <x-form.select name="mulai_target_baris" id="lineDropdown" :label="__('label.line')"
                                        :option="[$hafalan->mulai_target_baris => $hafalan->mulai_target_baris]" :selected="$hafalan->mulai_target_baris" />
                                </div>
                            </div>
                        </div>

                        {{-- END TARGET --}}
                        <div id="form-murojaah"
                            style="display: {{ $hafalan->jenis_kaldik != 'Ziyadah' ? 'block' : 'none' }}">
                            <x-section-form :label="__('label.target_end')" icon="bx bxs-graduation" />
                            <div class="row">
                                <div class="col-md-4">
                                    <x-form.select name="akhir_target_juz" id="endJuzDropdown" :label="__('label.juz')"
                                        :option="$target->pluck('juz', 'juz')->unique()->toArray()" :selected="$hafalan->akhir_target_juz" />
                                </div>
                                <div class="col-md-4">
                                    <x-form.select name="akhir_target_halaman" id="endPageDropdown" :label="__('label.page')"
                                        :option="[$hafalan->akhir_target_halaman => $hafalan->akhir_target_halaman]" :selected="$hafalan->akhir_target_halaman" />
                                </div>
                                <div class="col-md-4" id="endBarisGroup">
                                    <x-form.select name="akhir_target_baris" id="endLineDropdown" :label="__('label.line')"
                                        :option="[$hafalan->akhir_target_baris => $hafalan->akhir_target_baris]" :selected="$hafalan->akhir_target_baris" />
                                </div>
                            </div>
                        </div>

                        <x-form.button-submit :label="'Update Data'" :cancel-route="route('employee.hafalan.index')" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Select2 dan Script sama seperti di halaman create --}}
    @push('scripts')
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
                            url: `/hafalan/get-halaman/${juz}`,
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
                    $('#id-input').val(selectedOption.attr('data-id'));
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
    @endpush
@endsection
