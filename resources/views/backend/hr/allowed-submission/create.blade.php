@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/allowed-submission/create" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-1">
            <form method="POST" action="{{ route('hr.allowed-submission.store') }}">
                @csrf
                <div class="container py-2">
                    <div class="row mb-1">
                        <div class="col-md-6">
                            <input type="hidden" name="employee_id" id="selected-employees" class="form-control" readonly
                                value="{{ old('employee_id') }}" />

                            <label for="name">{{ __('label.name') }}</label>
                            <select id="selected_employee_id" class="form-select select2">
                                <option value="">-- Pilih Ustadz --</option>
                                @foreach ($employees as $ustadz)
                                    <option value="{{ $ustadz->id }}">{{ $ustadz->name }}</option>
                                @endforeach
                            </select>

                            @php
                                $position = [
                                    'logistik' => 'Logistik',
                                    'mudir' => 'Mudir',
                                    'wadir' => 'Wadir',
                                    'it' => 'IT',
                                    'sarpras' => 'Sarpras',
                                    'atk' => 'ATK',
                                    'bendahara' => 'Bendahara',
                                ];
                            @endphp

                            <div class="mt-3">
                                <x-form.select name="position" :label="__('label.position')" :option="$position" required />
                            </div>
                        </div>
                    </div>

                    <div class="pt-0">
                        <button type="submit" class="btn btn-primary">{{ __('label.save') }}</button>
                        <a href="{{ route('hr.allowed-submission.index') }}" id="btn-cancel"
                            class="btn btn-secondary">{{ __('label.cancel') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#selected_employee_id').select2({
                placeholder: 'Pilih Ustadz',
                allowClear: true
            });

            $('#selected_employee_id').on('change', function() {
                $('#selected-employees').val($(this).val());
            });

            let oldId = $('#selected-employees').val();
            if (oldId) {
                $('#selected_employee_id').val(oldId).trigger('change');
            }
        });
    </script>
@endpush
