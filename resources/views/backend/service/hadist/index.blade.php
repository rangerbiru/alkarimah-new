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
                <p class="mb-0" style="text-align: justify; line-height: 1.65">Rasulullah ﷺ bersabda: <br>

                    "Semoga Allah mencerahkan wajah seseorang yang mendengar hadisku, lalu menghafalnya, kemudian
                    menyampaikannya. Bisa jadi orang yang diberi tahu lebih memahami daripada orang yang mendengar
                    langsung."
                    (HR. Abu Dawud No. 3660, Tirmidzi No. 2656, dan Ibnu Majah No. 230)
                </p>
            </div>

        </div>
        <div class="card-hadist">
            @foreach ($listHadist as $hadist)
            <a href="{{ route('service.hadistById', ['id' => $hadist['slug']]) }}" class="card-hadist-body">
                <div class="top">
                    <img src="{{ asset('images/hadist/' . $hadist['image']) }}" alt="Hadist Image" width="90">
                </div>
                <div class="mt-3">
                    <h2 class="fs-16 mb-1">{{ $hadist['judul'] }}</h2>
                    <h6 class="fs-12 mb-0">{{ $hadist['total'] }} Hadist</h6>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endsection

    <style>
        .card-hadist {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            padding-bottom: 30px;
        }

        .card-hadist-body {
            padding: 20px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
        }

        .nomor h5 {
            margin-bottom: 0;
        }
    </style>