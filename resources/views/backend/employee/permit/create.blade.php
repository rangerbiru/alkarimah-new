@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/permit/create" />
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <form method="post" action="{{ route('employee.permit.store') }}" class="form-block" enctype="multipart/form-data">
                <div class="card">
                    <div class="card-body">
                        @csrf

                        <!-- Foto Section -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="preview-container">
                                    <div id="placeholder-preview"
                                        style="display: block; text-align: center; cursor: pointer;">
                                        <i class="bx bxs-camera icon-photo"></i>
                                        <p style="margin-top: 10px; font-size: 14px; color: #888;">Dokumen Pendukung</p>
                                    </div>

                                    <div class="d-flex flex-column gap-2 align-items-center">
                                        <img id="image-preview" src="#" alt="Preview Gambar" class="img-thumbnail"
                                            style="display: none; width: 150px; height: 150px; object-fit: cover; border-radius: 100px; cursor: pointer;">
                                        <p id="text-preview"
                                            style="display: none; margin-top: 2px; font-size: 14px; color: #888;">Ubah
                                            Dokumen
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

                                    <!-- Dokumen preview (ikon) -->
                                    <div id="document-preview" style="display: none; text-align: center;">
                                        <i class="bx bxs-file-blank icon-document"
                                            style="font-size: 72px; color: #555;"></i>
                                        <p id="document-filename" style="margin-top: 8px; font-size: 14px; color: #666;">
                                        </p>
                                        <p style="font-size: 12px; color: #888;">Klik untuk ubah</p>
                                    </div>

                                    <!-- Canvas tersembunyi untuk ambil screenshot -->
                                    <canvas id="photo-canvas" style="display: none;"></canvas>

                                    <!-- Input file tersembunyi -->
                                    <input type="file" name="attachment" id="photo" class="form-control"
                                        style="display: none;" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="permit_type_id">{{ __('label.employee_permit_type') }}</label>
                                <select id="permitType" name="permit_type_id" class="form-select select2" required>
                                    <option value="">-- Pilih Jenis Izin --</option>
                                    @foreach ($permitTypes as $type)
                                        <option value="{{ $type->id }}" data-level="{{ $type->level }}">
                                            {{ $type->permit_type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <x-form.date-picker name="date" picker-type="date" :label="__('label.date_permit')" :old="old('date', date('d-m-Y'))" />
                            </div>
                        </div>

                        {{-- Level 1 : Izin Jam --}}
                        <div class="row" id="level-1-fields" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label>Mulai Jam Izin</label>
                                <input type="time" name="permit_start_time" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Berapa Jam Izin</label>
                                <input type="number" name="permit_hour_total" class="form-control" min="1"
                                    placeholder="Contoh: 2">
                            </div>
                        </div>

                        {{-- Level 2 : Izin Hari --}}
                        <div class="row" id="level-2-fields" style="display: none;">
                            <div class="col-md-12 mb-3">
                                <label>Berapa Hari Izin</label>
                                <input type="number" name="permit_day_total" class="form-control" min="1"
                                    placeholder="Contoh: 3">
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-md-12">
                                <x-form.text-area name="reason" :label="__('label.reason')" :old="old('reason')" />
                            </div>
                        </div>

                        <x-form.button-submit :cancel-route="route('employee.permit.index')" />
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
                    <h5 class="modal-title" id="photoOptionModalLabel">Pilih Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <button type="button" id="btnUseCamera" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bx bx-camera me-2"></i> Gunakan Kamera
                    </button>
                    <button type="button" id="btnUseGallery" class="btn btn-outline-secondary w-100">
                        <i class="bx bx-file me-2"></i> Pilih dari Penyimpanan
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

        .icon-document {
            font-size: 72px;
            color: #555;
        }
    </style>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endpush


    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <script>
            $('#permitType').on('change', function() {
                const selectedOption = $(this).find(':selected');
                const level = selectedOption.data('level');

                $('#level-1-fields').hide();
                $('#level-2-fields').hide();

                $('#level-1-fields input, #level-2-fields input').val('');

                if (level == 1) {
                    $('#level-1-fields').show();
                } else if (level == 2) {
                    $('#level-2-fields').show();
                }
            });
        </script>


        <script>
            $(document).ready(function() {
                $('#permitType').select2({
                    placeholder: 'Pilih Jenis Izin',
                    allowClear: true
                });
            });
        </script>

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

                    placeholderPreview.style.display = 'none';
                    imagePreview.style.display = 'none';
                    textPreview.style.display = 'none';
                    cameraSection.style.display = 'block';

                } catch (err) {
                    alert('Tidak bisa mengakses kamera: ' + (err.message || err));
                    bootstrap.Modal.getInstance(modalElement).hide();
                }
            }

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

                    // ✅ PENTING: reset tampilan dokumen
                    document.getElementById('document-preview').style.display = 'none';

                    imagePreview.style.display = 'block';
                    textPreview.style.display = 'block';
                    placeholderPreview.style.display = 'none';
                    cameraSection.style.display = 'none';

                    if (currentStream) {
                        currentStream.getTracks().forEach(track => track.stop());
                        currentStream = null;
                    }
                }, 'image/jpeg', 0.9);
            }


            function cancelCamera() {
                if (currentStream) {
                    currentStream.getTracks().forEach(track => track.stop());
                    currentStream = null;
                }

                cameraSection.style.display = 'none';
                renderPreviewFromInput();
            }


            placeholderPreview.addEventListener('click', () => {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            });

            imagePreview.addEventListener('click', () => {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            });

            document.getElementById('btnUseCamera').addEventListener('click', () => {
                bootstrap.Modal.getInstance(modalElement).hide();
                startCamera();
            });

            document.getElementById('btnUseGallery').addEventListener('click', () => {
                imageInput.removeAttribute('capture');
                imageInput.click();
                bootstrap.Modal.getInstance(modalElement).hide();
            });

            document.getElementById('btn-switch-camera').addEventListener('click', () => {
                facingMode = facingMode === 'user' ? 'environment' : 'user';
                startCamera();
            });

            document.getElementById('btn-take-photo').addEventListener('click', takePhotoFromCamera);

            document.getElementById('btn-cancel-camera').addEventListener('click', cancelCamera);

            function resetPreviews() {
                placeholderPreview.style.display = 'block';
                imagePreview.style.display = 'none';
                document.getElementById('document-preview').style.display = 'none';
                textPreview.style.display = 'none';
            }

            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) {
                    resetPreviews();
                    return;
                }

                placeholderPreview.style.display = 'none';

                const fileType = file.type;
                const validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];

                if (validImageTypes.includes(fileType)) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        imagePreview.src = event.target.result;
                        imagePreview.style.display = 'block';
                        textPreview.style.display = 'block';
                        document.getElementById('document-preview').style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                } else {
                    const previewDoc = document.getElementById('document-preview');
                    const filename = file.name.length > 20 ?
                        file.name.substring(0, 20) + '...' :
                        file.name;

                    document.getElementById('document-filename').textContent = filename;
                    previewDoc.style.display = 'block';
                    imagePreview.style.display = 'none';
                    textPreview.style.display = 'none';
                }
            });

            document.getElementById('document-preview').addEventListener('click', () => {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            });

            function renderPreviewFromInput() {
                const file = imageInput.files[0];

                placeholderPreview.style.display = 'none';
                imagePreview.style.display = 'none';
                textPreview.style.display = 'none';
                document.getElementById('document-preview').style.display = 'none';

                if (!file) {
                    placeholderPreview.style.display = 'block';
                    return;
                }

                const validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];

                if (validImageTypes.includes(file.type)) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                        textPreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    document.getElementById('document-filename').textContent =
                        file.name.length > 20 ? file.name.substring(0, 20) + '...' : file.name;

                    document.getElementById('document-preview').style.display = 'block';
                }
            }
        </script>
    @endpush
@endsection
