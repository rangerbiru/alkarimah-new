    @extends('layouts.backend.index')

    @section('title', $title)
    @section('header')
        <x-section-page :label="$title" :icon="$icon" breadcrumb="academic/subject/create" />
    @endsection

    @section('content')
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('academic.subject.update', $subject->id) }}"
                    enctype="multipart/form-data" id="form" class="form-block">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-4">
                            <x-form.input-text name="name" :label="__('label.subject')" :old="$subject->name" />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            @php
                                $education = [
                                    'sd' => 'SD',
                                    'smp' => 'SMP',
                                    'sma' => 'SMA',
                                ];
                            @endphp
                            <label for="level_education">{{ __('label.choose_education_level') }}</label>
                            <select name="level_education" id="level_education" class="form-control" required>
                                <option value="">Pilih</option>
                                @foreach ($education as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ $subject->level_education == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mt-3">
                            <label for="lesson_hours">{{ __('label.lesson_hours') }}</label>
                            <div class="input-group form-group">
                                <input type="number" name="lesson_hours" id="lesson_hours"
                                    value="{{ $subject->lesson_hours }}" class="form-control" placeholder="Contoh: 2"
                                    required>
                                <span class="input-group-text">JP</span>
                            </div>
                        </div>
                    </div>

                    <x-form.button-submit :cancel-route="route('academic.subject.index')" />
                </form>
            </div>
        </div>
    @endsection

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            const error =
                "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"

            $(document).ready(function() {
                if (error != "")
                    setNotifInfo(error)
            })
        </script>

        <script>
            $(document).ready(function() {

                $('#level_education').select2({
                    placeholder: 'Pilih Tingkat Pendidikan',
                    allowClear: true
                });

                // $('#level_education').on('change', function() {
                //     $('#id_branch').val($(this).val());
                // });
            });
        </script>
    @endpush
