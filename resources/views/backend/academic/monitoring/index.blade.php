@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="academic/monitoring" />
@endsection

@section('content')
    <div class="container-fluid">
        {{-- Statistik --}}
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="badge bg-success p-2 rounded-circle">
                            <i class="bi bi-check-lg fs-5"></i>
                        </span>
                        <div>
                            <h2 class="mb-0">{{ $totalHadir }}</h2>
                            <small class="text-muted fs-14">Guru Hadir</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="badge bg-danger p-2 rounded-circle">
                            <i class="bi bi-x-lg fs-5"></i>
                        </span>
                        <div>
                            <h2 class="mb-0">{{ $totalBelumAbsen }}</h2>
                            <small class="text-muted fs-14">Belum Absen</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="badge bg-primary p-2 rounded-circle">
                            <i class="bi bi-people fs-5"></i>
                        </span>
                        <div>
                            <h2 class="mb-0">{{ $totalHadir + $totalBelumAbsen }}</h2>
                            <small class="text-muted fs-14">Total Guru</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <div class="card mb-4 shadow-sm">
            <form method="GET" action="{{ route('academic.monitoring.index') }}">
                <div class="card-body">
                    <div class="row align-items-center g-2">

                        <div class="col-sm-6 col-md-3">
                            <x-form.date-picker name="date" picker-type="date" :label="__('label.date')" :old="request('date', date('d-m-Y'))" />
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <x-form.select name="class" :label="__('label.class')" :option="$classList" :old="request('class')" />
                        </div>

                        <div class="col-12 col-md-auto d-flex gap-2">
                            <button class="btn btn-success">
                                Terapkan
                            </button>
                            <a href="{{ route('academic.monitoring.index') }}" class="btn btn-outline-secondary ">
                                Reset
                            </a>
                        </div>

                    </div>
                </div>
            </form>
        </div>

        {{-- Main Content --}}
        <div class="row">
            {{-- Table --}}
            <div class="col-lg-9">
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Jam</th>
                                    <th>Pelajaran</th>
                                    @foreach ($classHours as $classHour)
                                        <th>{{ $classHour->name ?? 'Kelas' }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @for ($jam = 1; $jam <= 8; $jam++)
                                    <tr>
                                        <td>{{ $jam }}</td>
                                        <td>Jam ke {{ $jam }}</td>

                                        @foreach ($classHours as $classHour)
                                            @php
                                                $detail = $classHour->details->firstWhere('jp_number', (string) $jam);
                                            @endphp

                                            <td>
                                                @if ($detail && $detail->id_employee)
                                                    <div class="fw-semibold">
                                                        {{ optional($detail->teacher)->name ?? '-' }}
                                                    </div>

                                                    @if ($detail->has_attendance ?? false)
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-check-circle"></i> Hadir
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger">
                                                            <i class="bi bi-x-circle"></i> Belum
                                                        </span>
                                                    @endif
                                                @elseif ($detail)
                                                    @if ($detail->has_attendance ?? false)
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-check-circle"></i> Hadir
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger">
                                                            <i class="bi bi-x-circle"></i> Belum
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted"><i>Tidak Ada Jadwal</i></span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Quick Insight --}}
            <div class="col-lg-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">
                            ⚠️ Quick Insight
                        </h6>

                        <ul class="list-unstyled small">
                            @if ($activeJam)
                                <li><strong>Jam aktif:</strong> Jam ke-{{ $activeJam }}</li>
                            @else
                                <li><strong>Jam aktif:</strong> Tidak ada</li>
                            @endif

                            <li class="mt-2">
                                <strong>Kelas belum absen:</strong><br>
                                @if (!empty($kelasBelumAbsen))
                                    {{ implode(', ', $kelasBelumAbsen) }}
                                @else
                                    <span class="text-success">Semua kelas sudah absen</span>
                                @endif
                            </li>

                            <li class="mt-2">
                                <strong>Total guru belum absen:</strong> {{ $totalBelumAbsen }} Orang
                            </li>

                            <li class="mt-2">
                                <strong>Rekomendasi:</strong><br>
                                @if (!empty($kelasBelumAbsen))
                                    <span>Perlu konfirmasi ke {{ count($kelasBelumAbsen) }}
                                        kelas</span>
                                @else
                                    <span class="text-success">Semua berjalan lancar</span>
                                @endif
                            </li>
                        </ul>

                        <hr>

                        <div class="d-flex flex-column gap-2 small">
                            <span class="badge bg-success text-start">Hadir</span>
                            <span class="badge bg-danger text-start">Belum Absen</span>
                            <span class="badge bg-warning text-dark text-start">Terlambat</span>
                            <span class="badge bg-info text-start">Izin</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
