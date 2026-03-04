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
                    @php
                        function convertToArabicNumber($number)
                        {
                            $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
                            return str_replace(range(0, 9), $arabicNumbers, $number);
                        }
                    @endphp
                    @foreach ($quranDataById['ayat'] as $quran)
                        <p class="ayat">
                            {{ $quran['teksArab'] }}
                            <span class="nomor-ayat">﴿{{ convertToArabicNumber($quran['nomorAyat']) }}﴾</span>
                        </p>
                        <p style="text-align: justify" class="fs-14">{{ $quran['nomorAyat'] }}.
                            {{ $quran['teksIndonesia'] }}
                        </p>

                        {{-- <select class="form-select audio-selector form-select-sm rounded-pill"
                            data-audio="{{ json_encode($quran['audio']) }}">
                            @foreach ($quran['audio'] as $qari => $audioUrl)
                                <option value="{{ $audioUrl }}">Qari {{ $qari }}</option>
                            @endforeach
                        </select> --}}

                        @php
                            $lastAudio = end($quran['audio']);
                        @endphp

                        <audio class="audio-player mt-2" controls style="width: 100%; height: 40px;">
                            <source src="{{ $lastAudio }}" type="audio/mpeg">
                        </audio>



                        <script>
                            document.querySelectorAll('.audio-selector').forEach(selector => {
                                selector.addEventListener('change', function() {
                                    let audioPlayer = this.nextElementSibling;
                                    audioPlayer.querySelector('source').src = this.value;
                                    audioPlayer.load();
                                    // audioPlayer.play();
                                });
                            });
                        </script>
                        <hr>
                    @endforeach

                </div>
            </div>
        </div>
    @endsection

    <style>
        .card-quran {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .card-quran-body {
            padding: 10px 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ayat {
            font-size: 28px;
            font-family: 'Amiri', serif;
            direction: rtl
        }

        .nomor-ayat {
            font-size: 18px;
        }
    </style>
