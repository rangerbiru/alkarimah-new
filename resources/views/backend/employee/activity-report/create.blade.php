@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/activity-report/create" />
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <form method="post" action="{{ route('employee.activity-report.store') }}" class="form-block"
                enctype="multipart/form-data">
                <div class="card">
                    <div class="card-body">
                        @csrf
                        <input type="hidden" name="id_employee" id="id_employee" value="{{ $idEmployee }}">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="preview-container">
                                    <!-- Placeholder awal -->
                                    <div id="placeholder-preview"
                                        style="display: block; text-align: center; cursor: pointer;">
                                        <i class="bx bxs-camera icon-photo"></i>
                                        <p style="margin-top: 10px; font-size: 14px; color: #888;">Foto Hasil Pekerjaan</p>
                                    </div>

                                    <div class="d-flex flex-column gap-2 align-items-center">
                                        <img id="image-preview" src="#" alt="Preview Gambar" class="img-thumbnail"
                                            style="display: none; width: 150px; height: 150px; object-fit: cover; border-radius: 100px; cursor: pointer;">
                                        <p id="text-preview"
                                            style="display: none; margin-top: 2px; font-size: 14px; color: #888;">Ubah Foto
                                        </p>
                                    </div>


                                    <!-- Input file tersembunyi -->
                                    <input type="file" name="photo" id="photo" class="form-control"
                                        accept="image/*" style="display: none;">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <x-form.select id="name" name="id_activity" :label="__('label.name_activity')" :option="$nameActivity->pluck('activity_name', 'id')"
                                    :old="old('id_activity')" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <x-form.text-area name="description" :label="__('label.description')" :old="old('description')" />
                            </div>
                        </div>
                        <x-form.button-submit :cancel-route="route('employee.activity-report.index')" />
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

    <script>
        const error =
            "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"

        $(document).ready(function() {
            if (error != "") setNotifInfo(error)
        })

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
                // Jika user batalkan pilih file, kembalikan ke placeholder
                imagePreview.style.display = 'none';
                placeholderPreview.style.display = 'block';
            }
        });
    </script>
@endsection
