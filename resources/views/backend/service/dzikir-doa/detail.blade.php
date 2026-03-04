@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
    <x-section-page-mobile :label="$title" :icon="$icon" />
@endsection

@section('content')
    <div class="mt-3 min-vh-100">
        <div class="row">
            <div class="card shadow-sm">
                <form action="{{ route('service.dzikirDoa') }}" method="GET" class="m-3">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Cari hadist..."
                            value="{{ request('q') }}">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </div>
                </form>
            </div>
            <div class="card-dzikir-doa">
                @forelse ($dataDzikirDoa as $item)
                    <div class="card-dzikir-doa-body">
                        <h5 class="nomor">{!! $item['title'] !!}</h5>
                        <p class="mb-0 mt-3"
                            style="text-align: justify; line-height: 1.65; font-size: 24px; direction: rtl">
                            {!! $item['arabic'] !!}
                        </p>
                        <p class="mb-0 mt-4" style="text-align: justify; line-height: 1.65; font-size: 14px">
                            {!! $item['arti'] !!}
                        </p>

                        <!-- Tombol untuk membuka modal -->
                        <button class="btn btn-primary mt-3 w-100" data-bs-toggle="modal"
                            data-bs-target="#modalPenjelasan{{ $item['id_dzikir_pp'] }}">
                            Lihat Penjelasan
                        </button>

                        <!-- Modal -->
                        <div class="modal fade" id="modalPenjelasan{{ $item['id_dzikir_pp'] }}" tabindex="-1"
                            aria-labelledby="modalLabel{{ $item['id_dzikir_pp'] }}" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalLabel{{ $item['id_dzikir_pp'] }}">
                                            {{ $item['title'] }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body fs-18" style="text-align: justify">
                                        {!! nl2br($item['penjelasan']) !!}
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card-dzikir-doa-body">
                        <h5 class="nomor">Dzikir Doa Tidak Tersedia</h5>
                    </div>
                @endforelse
            </div>
        </div>
    @endsection

    <style>
        .card-dzikir-doa {
            display: flex;
            flex-direction: column;
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

        .row>* {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
    </style>
