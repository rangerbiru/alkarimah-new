@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="academic/class-schedule" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('academic.class-schedule.save-manage', $class->id) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table" id="table-class">
                        <thead>
                            <tr>
                                <th class="text-center">{{ __('label.no') }}</th>
                                <th class="text-center">{{ __('label.lesson_hours') }}</th>
                                @foreach ($availableDays as $day)
                                    <th class="text-center">
                                        {{ __('label.' . $day) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex flex-column gap-2 w-100">
                    <button type="submit" class="btn btn-primary">{{ __('label.save') }}</button>
                    <a href="{{ route('academic.class-schedule.index') }}" id="btn-cancel"
                        class="btn btn-secondary">{{ __('label.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="{{ asset('vendors/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        const SUBJECT_LIST = @json($subject);
        const TEACHER_LIST = @json($teacher);
        const AVAILABLE_DAYS = @json($availableDays);
    </script>

    <script>
        function buildDaySelect(day, jpNumber, selectedSubjectId = null, selectedTeacherId = null) {
            let optionsSubject = `<option disabled selected>- Pilih Mapel -</option>`;
            SUBJECT_LIST.forEach(sbj => {
                const selected = sbj.id == selectedSubjectId ? 'selected' : '';
                optionsSubject += `<option value="${sbj.id}" ${selected}>${sbj.name}</option>`;
            });

            let optionsTeacher = `<option disabled selected>- Pilih Guru -</option>`;
            TEACHER_LIST.forEach(tch => {
                const selected = tch.id == selectedTeacherId ? 'selected' : '';
                optionsTeacher += `<option value="${tch.id}" ${selected}>${tch.name}</option>`;
            });

            return `
        <div class="d-flex flex-column gap-1">
            <select class="form-select select2-subject"
                    name="schedule[${jpNumber}][${day}][subject]"
                    data-day="${day}"
                    data-jp="${jpNumber}">
                ${optionsSubject}
            </select>
            <select class="form-select select2-teacher mt-1"
                    name="schedule[${jpNumber}][${day}][teacher]"
                    data-day="${day}"
                    data-jp="${jpNumber}">
                ${optionsTeacher}
            </select>
        </div>
    `;
        }



        $(document).ready(function() {
            let columns = [{
                    class: "align-middle text-center",
                    width: "50px",
                    searchable: false,
                    render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                },
                {
                    class: "align-middle ",
                    render: (data, type, row, meta) => {
                        const start = row.start_time.substring(0, 5);
                        const end = row.end_time.substring(0, 5);
                        return `
                    <div class="d-flex flex-column align-items-center gap-2">
                        <span class="badge bg-success fs-12">${row.label}</span>
                        <span class="text-body-tertiary">(${start} - ${end})</span>
                    </div>
                `;
                    }
                }
            ];

            AVAILABLE_DAYS.forEach(day => {
                columns.push({
                    class: "align-middle text-center",
                    render: function(data, type, row, meta) {
                        if (['istirahat_1', 'istirahat_2'].includes(row.jp_number)) {
                            return '<span class="text-muted fst-italic">Istirahat</span>';
                        }

                        const dayData = row.days[day] || {};
                        const subjectId = dayData.id_subject;
                        const teacherId = dayData.id_employee;

                        return buildDaySelect(day, row.jp_number, subjectId, teacherId);
                    }
                });
            });

            window.LaravelDataTables = window.LaravelDataTables || {};
            window.LaravelDataTables["table-class"] = $("#table-class").DataTable({
                language: {
                    search: "",
                    searchPlaceholder: `${label_search}...`,
                    lengthMenu: "_MENU_ Data",
                    emptyTable: label_nodata
                },
                ajax: {
                    url: "{{ route('academic.class-schedule.datatable-manage-schedule', $class->id) }}",
                    type: "POST"
                },
                processing: true,
                serverSide: true,
                deferRender: true,
                ordering: false,
                aLengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                columns: columns,
                drawCallback: function() {
                    $('.select2-subject').not('.select2-hidden-accessible').select2({
                        width: 'resolve',
                        dropdownParent: $('.table-responsive')
                    });
                    $('.select2-teacher').not('.select2-hidden-accessible').select2({
                        width: 'resolve',
                        dropdownParent: $('.table-responsive')
                    });
                }
            });

            $($.fn.dataTable.tables(true)).css('width', '100%');
        });
    </script>

    <style>
        .select2-container--default .select2-results__option {
            word-wrap: break-word;
            white-space: normal;
        }
    </style>
@endpush
