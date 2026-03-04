@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/item/create" />
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <form method="post" action="{{ route('hr.item.store') }}" class="form-block" enctype="multipart/form-data">
                <div class="card">
                    <div class="card-body">
                        @csrf

                        <!-- Foto Section -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="preview-container">
                                    <!-- Placeholder awal -->
                                    <div id="placeholder-preview"
                                        style="display: block; text-align: center; cursor: pointer;">
                                        <i class="bx bxs-camera icon-photo"></i>
                                        <p style="margin-top: 10px; font-size: 14px; color: #888;">Foto Barang</p>
                                    </div>

                                    <div class="d-flex flex-column gap-2 align-items-center">
                                        <img id="image-preview" src="#" alt="Preview Gambar" class="img-thumbnail"
                                            style="display: none; width: 150px; height: 150px; object-fit: cover; border-radius: 100px; cursor: pointer;">
                                        <p id="text-preview"
                                            style="display: none; margin-top: 2px; font-size: 14px; color: #888;">Ubah Foto
                                        </p>
                                    </div>

                                    <div id="camera-section" style="display: none; text-align: center; margin: 15px 0;">
                                        <video id="camera-preview" autoplay playsinline
                                            style="width: 100%; max-width: 300px; border-radius: 8px;"></video>
                                        <br><br>
                                        <button type="button" id="btn-switch-camera" class="btn btn-sm btn-secondary">Ganti
                                            ke Kamera
                                            Depan/Belakang</button>
                                        <button type="button" id="btn-take-photo" class="btn btn-sm btn-success ms-2">Ambil
                                            Foto</button>
                                        <button type="button" id="btn-cancel-camera"
                                            class="btn btn-sm btn-outline-secondary ms-2">Batal</button>
                                    </div>

                                    <!-- Canvas tersembunyi untuk ambil screenshot -->
                                    <canvas id="photo-canvas" style="display: none;"></canvas>

                                    <!-- Input file tersembunyi -->
                                    <input type="file" name="photo" id="photo" class="form-control"
                                        accept="image/*" style="display: none;">
                                </div>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div class="row">
                            <div class="col-md-6">
                                <x-form.input-text name="barcode" :label="__('label.barcode')" :old="old('barcode')" />
                            </div>
                            <div class="col-md-6">
                                <x-form.select name="category_id" :label="__('label.asset_category')" :option="$category" :old="old('category_id')" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <x-form.input-text name="name" :label="__('label.name')" :old="old('name')" />
                            </div>
                            <div class="col-md-6">
                                <x-form.select name="type" :label="__('label.type')" :option="$types" :old="old('type')" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <x-form.input-text name="merk" :label="__('label.merk')" :old="old('merk')" />
                            </div>
                            <div class="col-md-6">
                                <x-form.input-text name="price" :label="__('label.estimate_price')" :old="old('price')" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                @php
                                    $unit = [
                                        'kg' => 'Kilogram',
                                        'pcs' => 'Pcs',
                                        'unit' => 'Unit',
                                        'liter' => 'Liter',
                                        'box' => 'Box',
                                        'roll' => 'Roll',
                                        'meter' => 'Meter',
                                        'meterkuadrat' => 'Meter Kuadrat',
                                        'meterkubik' => 'Meter Kubik',
                                        'score' => 'Score',
                                        'rim' => 'Rim',
                                    ];
                                @endphp
                                <x-form.select name="unit" :label="__('label.unit')" :option="$unit" :old="old('unit')" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <x-form.text-area name="description" :label="__('label.description')" :old="old('description')" />
                            </div>
                        </div>

                        <x-form.button-submit :cancel-route="route('hr.item.index')" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Pilihan Kamera / Galeri -->
    <div class="modal fade" id="photoOptionModal" tabindex="-1" aria-labelledby="photoOptionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoOptionModalLabel">Ambil Foto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <button type="button" id="btnUseCamera" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bx bx-camera me-2"></i> Gunakan Kamera
                    </button>
                    <button type="button" id="btnUseGallery" class="btn btn-outline-secondary w-100">
                        <i class="bx bx-images me-2"></i> Pilih dari Galeri
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .icon-photo {
            font-size: 72px;
            color: #888;
        }

        .preview-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin-bottom: 20px;
        }
    </style>

    <script>
        const error =
            "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset";
        $(document).ready(function() {
            if (error != "") setNotifInfo(error)
        })

        // Inisialisasi variabel global
        let currentStream = null;
        let facingMode = 'environment';

        // Elemen DOM
        const imageInput = document.getElementById('photo');
        const placeholderPreview = document.getElementById('placeholder-preview');
        const imagePreview = document.getElementById('image-preview');
        const textPreview = document.getElementById('text-preview');
        const modalElement = document.getElementById('photoOptionModal');
        const cameraSection = document.getElementById('camera-section');
        const video = document.getElementById('camera-preview');
        const canvas = document.getElementById('photo-canvas');

        // Fungsi: Mulai kamera
        async function startCamera() {
            try {
                // Hentikan stream lama jika ada
                if (currentStream) {
                    currentStream.getTracks().forEach(track => track.stop());
                }

                const constraints = {
                    video: {
                        facingMode: facingMode
                    }
                };

                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                currentStream = stream;
                video.srcObject = stream;

                // Sembunyikan preview & placeholder, tampilkan kamera
                placeholderPreview.style.display = 'none';
                imagePreview.style.display = 'none';
                textPreview.style.display = 'none';
                cameraSection.style.display = 'block';

            } catch (err) {
                alert('Tidak bisa mengakses kamera: ' + (err.message || err));
                bootstrap.Modal.getInstance(modalElement).hide();
            }
        }

        // Fungsi: Ambil foto dari kamera
        function takePhotoFromCamera() {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(function(blob) {
                const file = new File([blob], 'camera-photo.jpg', {
                    type: 'image/jpeg'
                });
                const dt = new DataTransfer();
                dt.items.add(file);
                imageInput.files = dt.files;

                const url = URL.createObjectURL(blob);
                imagePreview.src = url;
                imagePreview.style.display = 'block';
                textPreview.style.display = 'block';
                cameraSection.style.display = 'none';
                placeholderPreview.style.display = 'none';

                // Stop kamera
                if (currentStream) {
                    currentStream.getTracks().forEach(track => track.stop());
                    currentStream = null;
                }
            }, 'image/jpeg', 0.9);
        }

        // Fungsi: Batal kamera
        function cancelCamera() {
            if (currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
                currentStream = null;
            }
            cameraSection.style.display = 'none';

            // Jika belum ada foto, tampilkan placeholder
            if (imagePreview.style.display !== 'block') {
                placeholderPreview.style.display = 'block';
            }
        }

        // Event: Klik placeholder atau preview → buka modal
        placeholderPreview.addEventListener('click', () => {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        });

        imagePreview.addEventListener('click', () => {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        });

        // Event: Pilih "Gunakan Kamera" → mulai kamera
        document.getElementById('btnUseCamera').addEventListener('click', () => {
            bootstrap.Modal.getInstance(modalElement).hide();
            startCamera();
        });

        // Event: Pilih "Pilih dari Galeri" → trigger input file
        document.getElementById('btnUseGallery').addEventListener('click', () => {
            imageInput.removeAttribute('capture');
            imageInput.click();
            bootstrap.Modal.getInstance(modalElement).hide();
        });

        // Event: Ganti kamera (depan/belakang)
        document.getElementById('btn-switch-camera').addEventListener('click', () => {
            facingMode = facingMode === 'user' ? 'environment' : 'user';
            startCamera();
        });

        // Event: Ambil foto dari kamera
        document.getElementById('btn-take-photo').addEventListener('click', takePhotoFromCamera);

        // Event: Batal kamera
        document.getElementById('btn-cancel-camera').addEventListener('click', cancelCamera);

        // Event: Input file (galeri) dipilih
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
                // Jika dibatalkan
                imagePreview.style.display = 'none';
                placeholderPreview.style.display = 'block';
            }
        });
    </script>
@endsection
