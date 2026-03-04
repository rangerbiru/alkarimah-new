@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="academic/class-hours/create" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-1">
            <form method="POST" action="{{ route('academic.class-hours.store') }}">
                @csrf
                <div class="container py-2">
                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input-text name="name" :label="__('label.name')" :old="old('name')" />
                        </div>
                    </div>
                    @php
                        $days = [
                            'senin' => 'Senin',
                            'selasa' => 'Selasa',
                            'rabu' => 'Rabu',
                            'kamis' => 'Kamis',
                            'jumat' => 'Jumat',
                            'sabtu' => 'Sabtu',
                            'minggu' => 'Minggu',
                        ];
                    @endphp

                    <div class="row">
                        <div class="col-md-6">
                            <x-form.select name="day[]" :label="__('label.day')" :option="$days" required multiple
                                class="form-control" />
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="">{{ __('label.building') }}</label>
                            <select name="branchname" id="branchname" class="form-select select2" required>
                                <option value="">-- Pilih Gedung --</option>
                                @foreach ($branch as $br)
                                    <option value="{{ $br->id }}">{{ $br->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="id_branch" id="id_branch" value="{{ old('id_branch') }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="">{{ __('label.class') }}</label>
                            <select name="classname" id="classname" class="form-select select2" required>
                                <option value="">-- Pilih Kelas --</option>
                                @foreach ($class as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="id_class" id="id_class" value="{{ old('id_class') }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <x-form.input-text type="time" name="start_time" :label="__('label.start_time')" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input-text type="time" name="end_time" :label="__('label.end_time')" />
                        </div>
                    </div>

                    <x-section-form :label="__('label.manage_class_hours')" icon="bx bx-timer" />

                    {{-- <div class="row">
                        <div class="col-md-6">
                            <x-form.input-text name="jp_count" type="number" :label="__('label.lesson_hours')" :old="old('jp_count')" />
                        </div>
                    </div> --}}

                    <div class="row">
                        <div class="row">
                            <div class="col-md-6">
                                <x-form.input-text type="number" id="jp_duration" name="jp_duration" :label="__('label.jp_duration')"
                                    :old="old('jp_duration')" :placeholder="'cth: 30'" />
                            </div>
                            <div class="col-md-6">
                                <x-form.input-text type="number" id="jp_total" name="jp_total" :label="__('label.jp_total')"
                                    :old="old('jp_total')" :placeholder="'cth: 10'" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <x-form.input-text type="number" id="break1_after" name="break1_after" :label="__('label.break1_after')"
                                    :old="old('break1_after')" :placeholder="'cth: 3'" />
                            </div>
                            <div class="col-md-6">
                                <x-form.input-text type="number" id="break2_after" name="break2_after" :label="__('label.break2_after')"
                                    :old="old('break2_after')" :placeholder="'cth: 6'" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <x-form.input-text type="number" id="break_duration_1" name="break_duration_1"
                                    :label="__('label.break_duration_1')" :old="old('break_duration_1')" :placeholder="'cth: 15'" />
                            </div>

                            <div class="col-md-3">
                                <x-form.input-text type="number" id="break_duration_2" name="break_duration_2"
                                    :label="__('label.break_duration_2')" :old="old('break_duration_2')" :placeholder="'cth: 15'" />
                            </div>


                            <div class="col-md-3 d-flex align-items-center mb-2">
                                <button type="button" class="btn btn-info w-100" id="generateJP">
                                    Generate Jam Pelajaran
                                </button>


                            </div>
                            <div class="col-md-3 d-flex align-items-center mb-2">
                                <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal"
                                    data-bs-target="#modalLessonHours">
                                    Lihat Jam Pelajaran
                                </button>
                            </div>
                        </div>

                    </div>


                    <div class="pt-0">
                        <button type="submit" class="btn btn-primary">{{ __('label.save') }}</button>
                        <a href="{{ route('academic.class-hours.index') }}" id="btn-cancel"
                            class="btn btn-secondary">{{ __('label.cancel') }}</a>
                    </div>
                </div>

                <div class="modal fade" id="modalLessonHours" tabindex="-1">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Lihat Jam Pelajaran</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">

                                <div id="lesson-container">
                                    <!-- Baris pertama -->
                                    <div class="row mb-3 lesson-row">
                                        <div class="col-md-3">
                                            <label>Jam Pelajaran</label>
                                            <select class="form-select-lg form-select jp-select">
                                                <option value="">-- Pilih --</option>
                                                <option value="1">Jam ke 1</option>
                                                <option value="2">Jam ke 2</option>
                                                <option value="3">Jam ke 3</option>
                                                <option value="4">Jam ke 4</option>
                                                <option value="5">Jam ke 5</option>
                                                <option value="6">Jam ke 6</option>
                                                <option value="7">Jam ke 7</option>
                                                <option value="8">Jam ke 8</option>
                                                <option value="9">Jam ke 9</option>
                                                <option value="10">Jam ke 10</option>
                                                <option value="11">Jam ke 11</option>
                                                <option value="12">Jam ke 12</option>
                                                <option value="13">Jam ke 13</option>
                                                <option value="14">Jam ke 14</option>
                                                <option value="15">Jam ke 15</option>
                                                <option value="istirahat_1">Istirahat 1</option>
                                                <option value="istirahat_2">Istirahat 2</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label>Jam Masuk</label>
                                            <input type="time" class="form-control start-input">
                                        </div>

                                        <div class="col-md-4">
                                            <label>Jam Selesai</label>
                                            <input type="time" class="form-control end-input">
                                        </div>

                                        <div class="col-md-1 d-flex align-items-end mt-2">
                                            <button type="button" class="btn btn-danger remove-row">Hapus</button>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" id="addLessonHour" class="btn btn-success mt-2">
                                    + Tambah Jam
                                </button>

                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>

                        </div>
                    </div>
                </div>
            </form>
        </div>

    </div>




@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>


    <script>
        $(document).ready(function() {

            $('#branchname').select2({
                placeholder: 'Pilih Gedung',
                allowClear: true
            });

            $('#classname').select2({
                placeholder: 'Pilih Kelas',
                allowClear: true
            });

            $('#branchname').on('change', function() {
                $('#id_branch').val($(this).val());
            });

            $('#classname').on('change', function() {
                $('#id_class').val($(this).val());
            });
        });
    </script>

    <script>
        $(document).ready(function() {

            function reindexRows() {
                $('#lesson-container .lesson-row').each(function(i) {
                    $(this).find('.jp-select').attr('name', `lesson_hours[${i}][jp]`);
                    $(this).find('.start-input').attr('name', `lesson_hours[${i}][start_time]`);
                    $(this).find('.end-input').attr('name', `lesson_hours[${i}][end_time]`);
                });
            }

            // Tambah Baris
            $("#addLessonHour").click(function() {
                let newRow = `
        <div class="row mb-3 lesson-row">
            <div class="col-md-3">
                <label>Jam Pelajaran</label>
                <select class="form-select-lg form-select jp-select">
                    <option value="">-- Pilih --</option>
                    <option value="1">Jam ke 1</option>
                    <option value="2">Jam ke 2</option>
                    <option value="3">Jam ke 3</option>
                    <option value="4">Jam ke 4</option>
                    <option value="5">Jam ke 5</option>
                    <option value="6">Jam ke 6</option>
                    <option value="7">Jam ke 7</option>
                    <option value="8">Jam ke 8</option>
                    <option value="9">Jam ke 9</option>
                    <option value="10">Jam ke 10</option>
                    <option value="11">Jam ke 11</option>
                    <option value="12">Jam ke 12</option>
                    <option value="13">Jam ke 13</option>
                    <option value="14">Jam ke 14</option>
                    <option value="15">Jam ke 15</option>
                    <option value="istirahat_1">Istirahat 1</option>
                    <option value="istirahat_2">Istirahat 2</option>
                </select>
            </div>

            <div class="col-md-4">
                <label>Jam Masuk</label>
                <input type="time" class="form-control start-input">
            </div>

            <div class="col-md-4">
                <label>Jam Selesai</label>
                <input type="time" class="form-control end-input">
            </div>

           <div class="col-md-1 d-flex align-items-end mt-2">
                                <button type="button" class="btn btn-danger remove-row">Hapus</button>
                            </div>
        </div>
        `;

                $("#lesson-container").append(newRow);

                reindexRows(); // update name index
            });

            // Hapus Baris
            $(document).on("click", ".remove-row", function() {
                $(this).closest(".lesson-row").remove();
                reindexRows(); // index diperbarui ulang
            });

            // Set index awal
            reindexRows();
        });
    </script>

    <script>
        $(document).ready(function() {

            function reindexRows() {
                $('#lesson-container .lesson-row').each(function(i) {
                    $(this).find('.jp-select').attr('name', `lesson_hours[${i}][jp]`);
                    $(this).find('.start-input').attr('name', `lesson_hours[${i}][start_time]`);
                    $(this).find('.end-input').attr('name', `lesson_hours[${i}][end_time]`);
                });
            }

            //  Function Format Waktu
            const formatTime = (date) => {
                return date.getHours().toString().padStart(2, '0') + ":" +
                    date.getMinutes().toString().padStart(2, '0');
            };

            // Generate Otomatis
            $("#generateJP").click(function() {

                let startTime = $("input[name='start_time']").val();
                let duration = parseInt($("#jp_duration").val());
                let jpTotal = parseInt($("#jp_total").val());
                let break1After = parseInt($("#break1_after").val());
                let break2After = parseInt($("#break2_after").val());
                let breakDuration1 = parseInt($("#break_duration_1").val());
                let breakDuration2 = parseInt($("#break_duration_2").val());

                if (!startTime || !duration || !jpTotal) {
                    alert("Pastikan jam mulai, durasi JP, dan jumlah JP diisi!");
                    return;
                }

                $("#generateJP").prop("disabled", true).text("Menghitung...");

                setTimeout(() => {

                    $("#lesson-container").html("");

                    let [h, m] = startTime.split(":").map(Number);
                    let current = new Date(2000, 1, 1, h, m);

                    const addRow = (label, value, start, end) => {
                        return `
                <div class="row mb-3 lesson-row">
                    <div class="col-md-3">
                        <label>Jam Pelajaran</label>
                        <select class="form-select jp-select">
                            <option value="${value}">${label}</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Jam Masuk</label>
                        <input type="time" class="form-control start-input" value="${formatTime(start)}">
                    </div>

                    <div class="col-md-4">
                        <label>Jam Selesai</label>
                        <input type="time" class="form-control end-input" value="${formatTime(end)}">
                    </div>
<div class="col-md-1 d-flex align-items-end mt-2">
                                <button type="button" class="btn btn-danger remove-row">Hapus</button>
                            </div>
                   
                </div>
            `;
                    };

                    for (let i = 1; i <= jpTotal; i++) {

                        let startJP = new Date(current);
                        let endJP = new Date(current);
                        endJP.setMinutes(endJP.getMinutes() + duration);

                        // Tambah JP row
                        $("#lesson-container").append(
                            addRow(`Jam ke ${i}`, i, startJP, endJP)
                        );

                        current = new Date(endJP);

                        // Tambahkan ISTIRAHAT 1
                        if (i === break1After) {
                            let breakStart = new Date(current);
                            let breakEnd = new Date(current);
                            breakEnd.setMinutes(breakEnd.getMinutes() + breakDuration1);

                            $("#lesson-container").append(
                                addRow("Istirahat 1", "istirahat_1", breakStart, breakEnd)
                            );

                            current = new Date(breakEnd);
                        }


                        // Tambahkan ISTIRAHAT 2
                        if (i === break2After) {
                            let breakStart2 = new Date(current);
                            let breakEnd2 = new Date(current);
                            breakEnd2.setMinutes(breakEnd2.getMinutes() + breakDuration2);

                            $("#lesson-container").append(
                                addRow("Istirahat 2", "istirahat_2", breakStart2, breakEnd2)
                            );

                            current = new Date(breakEnd2);
                        }

                    }

                    reindexRows();

                    $("#generateJP").prop("disabled", false).text("Generate Otomatis");

                }, 400);
            });


            // Hapus Baris Manual

            $(document).on("click", ".remove-row", function() {
                $(this).closest(".lesson-row").remove();
                reindexRows(); // index diperbarui ulang
            });
            reindexRows();
        });
    </script>
@endpush
