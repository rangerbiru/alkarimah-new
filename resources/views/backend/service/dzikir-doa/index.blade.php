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
                <p class="mb-0" style="text-align: justify; line-height: 1.65"><strong>Allah ﷻ berfirman dalam
                        Al-Qur’an:</strong><br>
                    "Dan apabila hamba-hamba-Ku bertanya kepadamu tentang Aku, maka (jawablah) bahwasanya Aku dekat. Aku
                    mengabulkan permohonan orang yang berdoa apabila ia berdoa kepada-Ku..."
                    (QS. Al-Baqarah: 186)
                </p>
            </div>

        </div>
        <div class="card-dzikir-doa">
            @foreach ($listDzikirDoa as $item)
            <a href="{{ route('service.dzikirDoaById', ['slug' => $item['slug']]) }}" class="card-dzikir-doa-body">
                <div class="top">
                    <img src="{{ asset('images/dzikir-doa/' . $item['image']) }}" alt="Dzikir Doa Image" width="90">
                </div>
                <div class="mt-3">
                    <h2 class="fs-16 mb-1">{{ $item['title'] }}</h2>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endsection

    <style>
        .card-dzikir-doa {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            padding-bottom: 30px;
        }

        .card-dzikir-doa-body {
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