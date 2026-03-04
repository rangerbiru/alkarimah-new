@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="academic/student-permit-group/edit" :breadcrumb-data="$groupId" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-1">
            <form method="POST" action="{{ route('academic.student-permit-group.update', $groupId) }}">
                @csrf
                @method('PUT')
                <div class="container py-2">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="ustadz_name">{{ __('label.pengampu_name') }}</label>
                            <select id="ustadz_name" class="form-select select2">
                                <option value="">-- Pilih Ustadz --</option>
                                @foreach ($ustadz_list as $ustadz)
                                    <option value="{{ $ustadz->id }}"
                                        {{ $permitGroup->ustadz_id == $ustadz->id ? 'selected' : '' }}>
                                        {{ $ustadz->name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="ustadz_id" id="ustadz_id"
                                value="{{ old('ustadz_id', $permitGroup->ustadz_id) }}" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <x-form.input-text name="group_name" label="Nama Grup" :old="old('group_name', $permitGroup->group_name)" required />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="student_name">{{ __('label.student_name') }}</label>
                            <div id="students-list">
                                <div class="mb-2">
                                    <select id="choosen_student" name="choosen_student" class="form-select select2">
                                        <option value="">-- Pilih Siswa --</option>
                                        @foreach ($student_list as $student)
                                            <option value="{{ $student->id }}">{{ $student->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <input type="hidden" name="student_name" id="student_name"
                                    value="{{ old('student_name', $permitGroup->student_name) }}" required>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary mt-2" id="btn-add-student">
                                <i class="fa fa-plus"></i> Tambah Siswa
                            </button>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <x-form.text-area name="description" label="Deskripsi (Opsional)" :old="old('description', $permitGroup->description)" />
                        </div>
                    </div>

                    <div class="pt-0">
                        <button type="submit" class="btn btn-primary">{{ __('label.save') }}</button>
                        <a href="{{ route('academic.student-permit-group.index') }}" id="btn-cancel"
                            class="btn btn-secondary">{{ __('label.cancel') }}</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="studentTable" class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
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
            $('#ustadz_name').select2().on('change', function() {
                $('#ustadz_id').val($(this).val());
            });

            $('#choosen_student').select2({
                placeholder: 'Pilih Siswa',
                allowClear: true
            });
            const table = $('#studentTable').DataTable();

            function loadStudents() {
                const students = JSON.parse(localStorage.getItem('studentList')) || @json($permitGroup->students);
                table.clear().draw();
                students.forEach((student, index) => {
                    table.row.add([
                        student.student_name,
                        `<button class="btn btn-sm btn-danger btn-delete" data-index="${index}">Hapus</button>`
                    ]).draw(false);
                });
                syncToHiddenInput();
            }

            $('#btn-add-student').on('click', function() {
                const studentId = $('#choosen_student').val();
                const studentName = $('#choosen_student option:selected').text();
                if (!studentId || studentName === '-- Pilih Siswa --') return alert(
                    'Silakan pilih siswa terlebih dahulu');

                let students = JSON.parse(localStorage.getItem('studentList')) || [];
                if (students.find(s => s.student_id === studentId)) return alert(
                    'Siswa sudah ditambahkan.');

                students.push({
                    student_id: studentId,
                    student_name: studentName
                });
                localStorage.setItem('studentList', JSON.stringify(students));
                loadStudents();
                $('#choosen_student').val('').trigger('change');
            });

            $('#studentTable tbody').on('click', '.btn-delete', function() {
                const index = $(this).data('index');
                let students = JSON.parse(localStorage.getItem('studentList')) || [];
                students.splice(index, 1);
                localStorage.setItem('studentList', JSON.stringify(students));
                loadStudents();
            });

            $('form').on('submit', function() {
                localStorage.removeItem('studentList');
            });

            loadStudents();
        });

        function syncToHiddenInput() {
            const students = JSON.parse(localStorage.getItem('studentList')) || [];
            $('#student_name').val(JSON.stringify(students));
        }

        const studentGroup = @json($studentGroup);

        console.log(studentGroup);

        if (studentGroup) {
            localStorage.setItem('studentList', JSON.stringify(studentGroup));
        }

        loadStudents();
    </script>
@endpush
