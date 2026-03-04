@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/activity-report/edit" :breadcrumb-data="$activity->id" />
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <form method="post" action="{{ route('employee.activity-report.update', $activity->id) }}" class="form-block"
                enctype="multipart/form-data">
                @csrf
                @method('PUT') <!-- penting untuk update -->

                <input type="hidden" name="id_employee" id="id_employee" value="{{ $idEmployee }}">

                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="preview-container mt-2">
                                    <!-- Placeholder default -->
                                    <div id="placeholder-preview"
                                        style="display: {{ $existingImage ? 'none' : 'block' }}; text-align: center; cursor: pointer;">
                                        <i class="bx bxs-camera icon-photo"></i>
                                        <p style="margin-top: 10px; font-size: 14px; color: #888;">Foto Hasil Pekerjaan</p>
                                    </div>

                                    <div class="d-flex flex-column gap-2 align-items-center">
                                        <img id="image-preview"
                                            src="{{ $existingImage ? asset('storage/activity-report/' . $existingImage) : '#' }}"
                                            alt="Preview Gambar" class="img-thumbnail"
                                            style="display: {{ $existingImage ? 'block' : 'none' }}; width: 150px; height: 150px; object-fit: cover; border-radius: 100px; cursor: pointer;">

                                        <p id="text-preview"
                                            style="display: {{ $existingImage ? 'block' : 'none' }}; margin-top: 2px; font-size: 14px; color: #888;">
                                            Ubah Foto
                                        </p>
                                    </div>

                                    <!-- Input file tersembunyi -->
                                    <input type="file" name="photo" id="photo" class="form-control"
                                        accept="image/*" style="display: none;">
                                </div>

                                {{-- Error message untuk photo --}}
                                @error('photo')
                                    <div class="text-danger mt-2" style="text-align: center;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <label for="name">{{ __('label.name_activity') }}</label>
                                <select id="name" name="id_activity" class="form-select select2" required>
                                    @foreach ($nameActivity->pluck('activity_name', 'id') as $key => $value)
                                        <option value="{{ $key }}"
                                            {{ old('id_activity', $activity->id_activity) == $key ? 'selected' : '' }}>
                                            {{ $value }}</option>
                                    @endforeach
                                </select>
                                @error('id_activity')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mt-3">
                                <x-form.text-area name="description" :label="__('label.description')" :old="$activity->description" />
                                @error('description')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <x-form.button-submit :cancel-route="route('employee.activity-report.index')" submit-label="Simpan Perubahan" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <style>
        .icon-photo {
            font-size: 72px;
            color: #888
        }

        .preview-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


        <script>
            $(document).ready(function() {
                // Select2
                $('#name').select2();
            });

            const imageInput = document.getElementById('photo');
            const placeholderPreview = document.getElementById('placeholder-preview');
            const imagePreview = document.getElementById('image-preview');
            const textPreview = document.getElementById('text-preview');

            function triggerFileInput() {
                imageInput.click();
            }

            placeholderPreview.addEventListener('click', triggerFileInput);
            imagePreview.addEventListener('click', triggerFileInput);

            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        imagePreview.src = event.target.result;
                        imagePreview.style.display = 'block';
                        textPreview.style.display = 'block';
                        placeholderPreview.style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Jika user batalkan pilih file, kembalikan ke foto lama atau placeholder
                    @if ($existingImage)
                        imagePreview.src = "{{ asset('storage/activity-report/' . $existingImage) }}";
                        imagePreview.style.display = 'block';
                        textPreview.style.display = 'block';
                        placeholderPreview.style.display = 'none';
                    @else
                        imagePreview.style.display = 'none';
                        textPreview.style.display = 'none';
                        placeholderPreview.style.display = 'block';
                    @endif
                }
            });

            // Tampilkan notifikasi error jika ada
            const error =
                "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset";

            $(document).ready(function() {
                if (error != "") setNotifInfo(error);
            });
        </script>
    @endpush
@endsection
