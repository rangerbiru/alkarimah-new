@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
    <x-section-page-mobile :label="$title" :icon="$icon" />
@endsection

@section('content')
    <div class="mt-3 min-vh-100">
        <div class="row">
            <div class="card shadow-sm">
                <div class="card-body">
                    {{-- <h4 id="user-location-container">
                        <span id="user-location"></span> <span id="current-date"></span>
                    </h4> --}}
                    <div class="user-location-container">
                        <h4 id="user-location"></h4>
                        <h6 id="current-date"></h6>
                    </div>


                    <button class="btn btn-info w-100 mb-3 mt-2" onclick="getUserLocation()">
                        <i class="fa-solid fa-location-dot"></i> Cek Lokasi
                    </button>

                    <div id="prayer-times">Mengambil lokasi...</div>
                </div>
            </div>
        </div>
    @endsection


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            checkLocation();
        });

        function checkLocation() {
            const latitude = localStorage.getItem("latitude");
            const longitude = localStorage.getItem("longitude");
            const locationName = localStorage.getItem("locationName");

            if (latitude && longitude && locationName) {
                document.getElementById("user-location").innerText = locationName;

                const today = new Date();
                const options = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                document.getElementById("current-date").innerText = today.toLocaleDateString('id-ID', options);

                fetchPrayerTimes(latitude, longitude);
            } else {
                getUserLocation();
            }
        }

        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;

                        localStorage.setItem("latitude", latitude);
                        localStorage.setItem("longitude", longitude);

                        fetchPrayerTimes(latitude, longitude);
                    },
                    function(error) {
                        console.error("Gagal mendapatkan lokasi:", error);
                        document.getElementById("prayer-times").innerHTML = "Aktifkan izin lokasi di browser Anda.";
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                document.getElementById("prayer-times").innerHTML = "Browser tidak mendukung geolocation.";
            }
        }

        function fetchPrayerTimes(latitude, longitude) {
            fetch(`/service/jadwal-sholat/lokasi?latitude=${latitude}&longitude=${longitude}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById("prayer-times").innerHTML = data.error;
                        return;
                    }

                    const locationName = data.name;
                    localStorage.setItem("locationName", locationName);

                    document.getElementById("user-location").innerText = locationName;

                    const today = new Date();
                    const todayDate = `${today.getFullYear()}-${today.getMonth() + 1}-${today.getDate()}`;
                    const todayPray = data.prayers.find(pray => pray.date === todayDate);

                    if (todayPray) {
                        document.getElementById("prayer-times").innerHTML = `
                            <div class='time-pray'><p>Subuh</p><p>${todayPray.time.subuh}</p></div>
                            <div class='time-pray'><p>Dzuhur</p><p>${todayPray.time.dzuhur}</p></div>
                            <div class='time-pray'><p>Ashar</p><p>${todayPray.time.ashar}</p></div>
                            <div class='time-pray'><p>Maghrib</p><p>${todayPray.time.maghrib}</p></div>
                            <div class='time-pray'><p>Isya</p><p>${todayPray.time.isya}</p></div>
                        `;
                    } else {
                        document.getElementById("prayer-times").innerText = "Jadwal sholat hari ini tidak ditemukan.";
                    }
                })
                .catch(error => {
                    console.error("Gagal mengambil jadwal sholat:", error);
                    document.getElementById("prayer-times").innerText = "Gagal mengambil jadwal sholat.";
                });
        }
    </script>


    <style>
        .user-location-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #location-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            margin-top: 10px;
            margin-bottom: 20px;
            width: 100%;
        }

        #location-btn:hover {
            background-color: #0056b3;
        }

        #location-btn i {
            font-size: 18px;
        }

        .time-pray {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 20px;
        }
    </style>
