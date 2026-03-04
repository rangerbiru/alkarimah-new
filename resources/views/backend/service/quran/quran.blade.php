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
                    <p class="mb-0" style="text-align: justify">Bacalah Al-Qur'an, karena sesungguhnya ia akan menjadi
                        syafaat bagi para pembacanya di
                        hari kiamat (HR. Muslim)</p>
                </div>

            </div>
            <div class="card-quran">
                @foreach ($quranData as $quran)
                    <a href="{{ route('service.quranById', ['id' => $quran['nomor']]) }}" class="card-quran-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="nomor">
                                <h5>{{ $quran['nomor'] }}.</h5>
                            </div>
                            <div class="title">
                                <h5 class="fs-16 mb-1">{{ $quran['namaLatin'] }}</h5>
                                <p class="fs-12 mb-0">{{ $quran['arti'] }}</p>
                            </div>
                        </div>
                        <div class="right">
                            <h6 class="mb-0 text-end">{{ $quran['nama'] }}</h6>
                            <p class="mb-0">{{ $quran['jumlahAyat'] }} Ayat</p>
                        </div>
                    </a>
                @endforeach
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

        .nomor h5 {

            margin-bottom: 0;
        }
    </style>
