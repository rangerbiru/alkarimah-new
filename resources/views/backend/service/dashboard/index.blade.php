@extends('layouts.mobile.index')

@section('title', $title)
@section('content')
    <div id="carouselExampleControls" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="{{ asset('images/slides/1.png') }}" class="d-block w-100" alt="...">
            </div>
            <div class="carousel-item">
                <img src="{{ asset('images/slides/2.png') }}" class="d-block w-100" alt="...">
            </div>
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
        <div class="col-4 text-center">
            <a href="#" onclick="maintenance()" class="btn-icon text-grey">
                <img src="{{ asset('images/icons/assessment-academic.png') }}" />

                <div class="mt-1 text">{{ __('label.academic_assessment') }}</div>
            </a>
        </div>
        <div class="col-4 text-center">
            <a href="{{ route('academic.tahfidz.index') }}" class="btn-icon text-grey">
                <img src="{{ asset('images/icons/assessment-tahfidz.png') }}" />

                <div class="mt-1 text">{{ __('label.tahfidz_assessment') }}</div>
            </a>
        </div>
        <div class="col-4 text-center">
            <a href="#" onclick="maintenance()" class="btn-icon text-grey">
                <img src="{{ asset('images/icons/report.png') }}" />

                <div class="mt-1 text">{{ __('label.report') }}</div>
            </a>
        </div>
        <div class="col-4 text-center">
            <a href="{{ route('academic.student-permit.index') }}" class="btn-icon text-grey">
                <img src="{{ asset('images/icons/permit.png') }}" />

                <div class="mt-1 text">{{ __('label.student_permit') }}</div>
            </a>
        </div>
        <div class="col-4 text-center">
            <a href="#" onclick="maintenance()" class="btn-icon text-grey">
                <img src="{{ asset('images/icons/donation.png') }}" />

                <div class="mt-1 text">{{ __('label.donation') }}</div>
            </a>
        </div>
        <div class="col-4 text-center">
            <a href="#" onclick="maintenance()" class="btn-icon text-grey">
                <img src="{{ asset('images/icons/ibbas-mart.png') }}" />

                <div class="mt-1 text">{{ __('label.ibbas_mart') }}</div>
            </a>
        </div>
    </div>

    <div class="card mt-4" style="border-radius: 20px;">
        <div class="card-body">
            <div class="row g-1 text-center">
                <div class="col">
                    <a href="{{ route('service.quran.index') }}" class="btn-icon-small text-grey">
                        <img src="{{ asset('images/icons/quran.png') }}" class="icon-custom-small" />

                        <div class="mt-1 text">{{ __('label.alquran') }}</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('service.hadist') }}" class="btn-icon-small text-grey">
                        <img src="{{ asset('images/icons/hadits.png') }}" class="icon-custom-small" />

                        <div class="mt-1 text">{{ __('label.hadist') }}</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('service.posterDakwah') }}" class="btn-icon-small text-grey">
                        <img src="{{ asset('images/icons/poster-dakwah.png') }}" />

                        <div class="mt-1 text">{{ __('label.poster_dakwah') }}</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('service.jadwalSholat') }}" class="btn-icon-small text-grey">
                        <img src="{{ asset('images/icons/lokasi.png') }}" class="icon-custom-small" />

                        <div class="mt-1 text">{{ __('label.jadwal_sholat') }}</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('service.dzikirDoa') }}" class="btn-icon-small text-grey">
                        <img src="{{ asset('images/icons/dzikir-doa.png') }}" />

                        <div class="mt-1 text">{{ __('label.dzikir_and_doa') }}</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div class="d-flex">
            <div>
                <h6 class="title-line">{{ __('label.activity_history') }}</h6>
            </div>
            <div class="ms-auto">
                <a href="{{ route('service.activity.index') }}"><u>Lihat Semua</u></a>
            </div>
        </div>

        @if ($activity->count() == 0)
            {{ __('string.there_is_no_activity_yet') }}
        @else
            @foreach ($activity as $a)
                <div class="card card-history mb-2">
                    <div class="card-body p-2">
                        <div class="d-flex">
                            <div>
                                <div class="icon">
                                    <i class="{{ $a->icon }}"></i>
                                </div>
                            </div>
                            <div class="text">
                                <b>{{ $a->title }}</b><br />
                                <small>
                                    "{{ $a->message }}"
                                </small>
                                <div class="text-muted mt-1">
                                    <small>{{ __('label.today') . ', ' . date('H:i', strtotime($a->created_at)) . ' WIB' }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
    function maintenance() {
        Swal.fire({
            icon: "error",
            title: "Mohon Maaf",
            text: "Sedang dalam maintenance/perbaikan",
        });
    }
</script>

@push('styles')
    <style>
        .btn-icon img {
            width: 55px;
        }

        .btn-icon .text {
            font-size: 12px;
        }

        .btn-icon-small img {
            width: 45px;
        }

        .btn-icon-small .text {
            font-size: 8px;
        }

        .card-history .icon {
            background: var(--input-border);
            border-radius: 50%;
            padding: 6px 10px;
            font-size: 25px;
            margin-right: 10px;
        }

        .card-history .text {
            font-size: 12px;
            line-height: 15px;
            padding-top: 3px;
        }

        .icon-custom-small {
            padding: 5px;
            border: 2px solid #e6e7e8;
            border-radius: 10px;
            width: 48px;
        }
    </style>
@endpush
