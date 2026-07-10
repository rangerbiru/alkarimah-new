@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/attendance/location/create" />
@endsection

@section('content')
    <form method="POST" action="{{ route('hr.attendance.location.store') }}">
        @csrf
        <div class="card">
            <div class="card-body p-1">

                <div class="container py-2">
                    <div class="row mb-1">
                        <div class="col-md-6">
                            <x-form.input-text type="text" name="location_name" :label="__('label.location_name')" :old="old('location_name')"
                                required />
                        </div>
                    </div>

                    <div class="row mb-1">
                        <div class="col-md-6">
                            <x-form.select :option="$groupId" id="groupName" name="attendance_group_id" :label="__('label.group_name')"
                                :old="old('attendance_group_id')" required />
                        </div>
                    </div>

                    <div class="row mb-1">
                        <div class="col-md-6">
                            <label>{{ __('label.location_coordinate') }}</label>
                            <div class="input-group form-group">
                                <input type="text" id="coordinate" name="coordinate" class="form-control"
                                    value="{{ old('coordinate') }}" required readonly>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#mapModal">
                                    Pilih Lokasi
                                </button>
                            </div>
                        </div>
                    </div>



                    @php
                        $locations = [
                            'ma alkarimah putra' => 'MA alkarimah Putra',
                            'ma alkarimah putri' => 'MA alkarimah Putri',
                            'ponpes alkarimah' => 'Ponpes alkarimah',
                            'ponpes taman surga' => 'Ponpes Taman Surga',
                        ];
                    @endphp

                    <div class="row mb-1">
                        <div class="col-md-6">
                            <x-form.select name="attendance_location" :label="__('label.location_exact')" :option="$locations" required
                                class="form-control" />
                        </div>
                    </div>



                    <div class="pt-0">
                        <button type="submit" class="btn btn-primary">{{ __('label.save') }}</button>
                        <a href="{{ route('hr.attendance.group.index') }}" id="btn-cancel"
                            class="btn btn-secondary">{{ __('label.cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Map -->
        <div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Pilih Lokasi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="map" style="height:500px; width:100%;"></div>

                        <div class="row mb-1">
                            <div class="col-md-6">
                                <label>{{ __('label.location_radius') }}</label>
                                <div class="input-group form-group">
                                    <input type="number" name="attendance_radius" class="form-control"
                                        value="{{ old('attendance_radius') }}" required>
                                    <span class="input-group-text">Meter</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <script>
        let map;
        let marker;
        let circle;

        document.getElementById('mapModal').addEventListener('shown.bs.modal', function() {
            if (!map) {
                // Ambil koordinat default dari input coordinate (jika ada)
                let defaultCoord = document.getElementById("coordinate").value;
                let defaultLatLng = defaultCoord ? defaultCoord.split(",") : [-7.476066, 110.895483];

                // Inisialisasi map
                map = L.map('map').setView([parseFloat(defaultLatLng[0]), parseFloat(defaultLatLng[1])], 15);

                // Tambahkan tile OpenStreetMap
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                // Jika sudah ada koordinat, pasang marker awal
                if (defaultCoord) {
                    marker = L.marker([parseFloat(defaultLatLng[0]), parseFloat(defaultLatLng[1])]).addTo(map);
                }

                // Klik pada map untuk pilih lokasi pusat
                map.on('click', function(e) {
                    const lat = e.latlng.lat;
                    const lng = e.latlng.lng;

                    // Hapus marker lama
                    if (marker) {
                        map.removeLayer(marker);
                    }

                    // Pasang marker baru
                    marker = L.marker([lat, lng]).addTo(map);

                    // Simpan koordinat ke input
                    document.getElementById("coordinate").value = lat + "," + lng;

                    // Jika admin sudah isi radius → tampilkan lingkaran
                    updateCircle(lat, lng);
                });
            }

            // Perbaiki ukuran saat modal dibuka
            setTimeout(() => {
                map.invalidateSize();
            }, 200);
        });

        // Fungsi menggambar lingkaran berdasarkan radius
        function updateCircle(lat, lng) {
            const radius = document.querySelector("[name='attendance_radius']").value;

            if (!radius || !lat || !lng) return;

            // Hapus lingkaran lama
            if (circle) {
                map.removeLayer(circle);
            }

            // Gambar lingkaran baru
            circle = L.circle([lat, lng], {
                radius: parseFloat(radius), // dalam meter
                color: 'blue',
                fillColor: '#cce5ff',
                fillOpacity: 0.3
            }).addTo(map);
        }

        // Event ketika admin ubah radius manual di input
        document.querySelector("[name='attendance_radius']").addEventListener('input', function() {
            let coord = document.getElementById("coordinate").value;
            if (coord) {
                let [lat, lng] = coord.split(",");
                updateCircle(parseFloat(lat), parseFloat(lng));
            }
        });
    </script>
@endpush
