@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/attendance/location/edit" :breadcrumb-data="$location->id" />
@endsection

@section('content')
    <form method="POST" action="{{ route('hr.attendance.location.update', $location->id) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body p-1">

                <div class="container py-2">
                    <div class="row mb-1">
                        <div class="col-md-6">
                            <x-form.input-text type="text" name="location_name" :label="__('label.location_name')" :old="old('location_name', $location->location_name)"
                                required />
                        </div>
                    </div>

                    <div class="row mb-1">
                        <div class="col-md-6">
                            <x-form.select :option="$groupId" id="groupName" name="attendance_group_id" :label="__('label.group_name')"
                                :old="old('attendance_group_id', $location->attendance_group_id)" required />
                        </div>
                    </div>

                    <div class="row mb-1">
                        <div class="col-md-6">
                            <label>{{ __('label.location_coordinate') }}</label>
                            <div class="input-group form-group">
                                <input type="text" id="coordinate" name="coordinate" class="form-control"
                                    value="{{ $location->coordinate }}" required readonly>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#mapModal">
                                    Ubah Lokasi
                                </button>
                            </div>
                        </div>
                    </div>

                    @php
                        $locations = [
                            'ma al-karimah putra' => 'MA Al-Karimah Putra',
                            'ma al-karimah putri' => 'MA Al-Karimah Putri',
                            'ponpes al-karimah' => 'Ponpes Al-Karimah',
                        ];
                    @endphp

                    <div class="row mb-1">
                        <div class="col-md-6">
                            <x-form.select name="attendance_location" :label="__('label.location_exact')" :option="$locations" :old="old('attendance_location', $location->attendance_location)"
                                required />
                        </div>
                    </div>

                    <div class="pt-0">
                        <button type="submit" class="btn btn-primary">{{ __('label.update') }}</button>
                        <a href="{{ route('hr.attendance.location.index') }}" id="btn-cancel"
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

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label>{{ __('label.location_radius') }}</label>
                                <div class="input-group form-group">
                                    <input type="number" name="attendance_radius" class="form-control"
                                        value="{{ $location->attendance_radius }}" required>
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
                // Ambil koordinat & radius dari database
                let coordValue = document.getElementById("coordinate").value;
                let [lat, lng] = coordValue ? coordValue.split(",").map(parseFloat) : [-7.476066, 110.895483];
                let radiusValue = parseFloat(document.querySelector("[name='attendance_radius']").value) || 100;

                // Inisialisasi map
                map = L.map('map').setView([lat, lng], 16);

                // Tambahkan tile dari OpenStreetMap
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                // Pasang marker dan lingkaran awal
                marker = L.marker([lat, lng]).addTo(map);
                circle = L.circle([lat, lng], {
                    radius: radiusValue,
                    color: 'blue',
                    fillColor: '#cce5ff',
                    fillOpacity: 0.3
                }).addTo(map);

                // Klik pada map untuk ubah lokasi
                map.on('click', function(e) {
                    const lat = e.latlng.lat;
                    const lng = e.latlng.lng;

                    // Hapus marker & lingkaran lama
                    if (marker) map.removeLayer(marker);
                    if (circle) map.removeLayer(circle);

                    // Tambah marker baru
                    marker = L.marker([lat, lng]).addTo(map);

                    // Simpan ke input
                    document.getElementById("coordinate").value = lat + "," + lng;

                    // Gambar ulang lingkaran
                    updateCircle(lat, lng);
                });
            }

            setTimeout(() => map.invalidateSize(), 200);
        });

        // Fungsi update circle berdasarkan input radius
        function updateCircle(lat, lng) {
            const radius = parseFloat(document.querySelector("[name='attendance_radius']").value);
            if (!radius) return;

            if (circle) map.removeLayer(circle);

            circle = L.circle([lat, lng], {
                radius: radius,
                color: 'blue',
                fillColor: '#cce5ff',
                fillOpacity: 0.3
            }).addTo(map);
        }

        // Saat radius berubah → update area
        document.querySelector("[name='attendance_radius']").addEventListener('input', function() {
            const coord = document.getElementById("coordinate").value;
            if (coord) {
                const [lat, lng] = coord.split(",").map(parseFloat);
                updateCircle(lat, lng);
            }
        });
    </script>
@endpush
