    @extends('layouts.backend.index')

    @section('title', $title)
    @section('header')
        <x-section-page :label="$title" :icon="$icon" breadcrumb="academic/subject/create" />
    @endsection

    @section('content')
        <div class="card">
            <div class="card-body">
                <form method="post" action="{{ route('academic.subject.store') }}" class="form-block">
                    @csrf

                    <div class="row">
                        <div class="col-md-4">
                            <x-form.input-text name="name" :label="__('label.subject')" :old="old('name')" />
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
                            <x-form.select name="level_education" :label="__('label.choose_education_level')" :option="$education" required />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <label for="lesson_hours">{{ __('label.lesson_hours') }}</label>
                            <div class="input-group form-group">
                                <input type="number" name="lesson_hours" id="lesson_hours" class="form-control"
                                    placeholder="Contoh: 2" required>
                                <span class="input-group-text">JP</span>
                            </div>
                        </div>
                    </div>

                    <x-form.button-submit :cancel-route="route('academic.subject.index')" />
                </form>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script>
            const error =
                "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"

            $(document).ready(function() {
                if (error != "")
                    setNotifInfo(error)
            })
        </script>
    @endpush
