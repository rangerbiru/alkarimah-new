@extends('layouts.mobile.index')

@section('title', $title)
@section('content')
    <div id="carouselExampleControls" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="{{ asset('images/slides/banner1.jpeg') }}" class="d-block w-100" alt="...">
            </div>
            {{-- <div class="carousel-item">
            <img src="{{ asset('images/slides/2.png') }}" class="d-block w-100" alt="...">
        </div> --}}
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleControls" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleControls" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <div class="row mt-4">
        <div class="col-3 text-center">
            <a href="{{ route('finance.payment.index') }}" class="btn-icon text-grey">
                <img src="{{ asset('images/icons/pembayaran.svg') }}" />

                <div class="mt-1 text">{{ __('label.payment') }}</div>
            </a>
        </div>
        <div class="col-3 text-center">
            <a href="{{ route('finance.balance.index') }}" class="btn-icon text-grey">
                <img src="{{ asset('images/icons/topup-saldo.svg') }}" />

                <div class="mt-1 text">{{ __('label.topup_balance') }}</div>
            </a>
        </div>
        <div class="col-3 text-center">
            <a href="{{ route('finance.savings.index') }}" class="btn-icon text-grey">
                <img src="{{ asset('images/icons/tabungan.svg') }}" />

                <div class="mt-1 text">{{ __('label.savings') }}</div>
            </a>
        </div>
        <div class="col-3 text-center">
            <a href="{{ route('academic.student.index') }}" class="btn-icon text-grey">
                <img src="{{ asset('images/icons/siswa.svg') }}" />

                <div class="mt-1 text">{{ __('label.student') }}</div>
            </a>
        </div>
    </div>

    <div class="mt-4">
        <h6 class="title-line">{{ __('label.transaction_history') }}</h6>

        @if (empty($transaction))
            <div class="my-3">
                <small>{{ __('string.no_transaction_yet') }}</small>
            </div>
        @else
            @foreach ($transaction as $t)
                <div class="card card-history mb-2">
                    <div class="card-body p-2">
                        <div class="d-flex">
                            <div class="icon">
                                <i class="{{ $t->icon }}"></i>
                            </div>
                            <div class="text">
                                {{ $t->name }}<br />
                                <small
                                    class="text-muted">{{ $t->flag . ' - ' . Common::dateFormat($t->paid_at, 'dd mmm yyyy, hh:ii WIB') }}</small>
                            </div>
                            <div class="text ms-auto text-end">
                                <span
                                    class="{{ $t->total_class }}">{{ number_format($t->total, 0, '', '.') }}</span><br />
                                <small class="text-muted">{{ $t->method }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection

@push('styles')
    <style>
        .btn-icon img {
            width: 55px;
        }

        .btn-icon .text {
            font-size: 10px;
        }

        .title-line {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .title-line:after {
            background: #cad0da;
            height: 1px;
            flex: 1;
            content: '';
            margin-left: 10px;
        }

        .title-line:before {
            background: none;
        }

        .card-history .icon {
            background: var(--input-border);
            border-radius: 50%;
            padding: 6px 10px;
            font-size: 14px;
            margin-right: 7px;
        }

        .card-history .text {
            font-size: 12px;
            line-height: 15px;
            padding-top: 3px;
        }
    </style>
@endpush
