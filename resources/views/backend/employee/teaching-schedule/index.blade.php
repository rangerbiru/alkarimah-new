@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/teaching-schedule" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="mt-0">
                @forelse ($teachingSchedule as $schedule)
                    <a href="{{ route('employee.teaching-schedule.show', $schedule->id) }}">
                        <div class="mb-3">
                            @php
                                if ($schedule->is_today) {
                                    if ($schedule->journal_filled) {
                                        $bgClass = 'bg-success text-white';
                                        $badgeClass = 'bg-light text-success';
                                    } else {
                                        $bgClass = 'bg-danger text-white';
                                        $badgeClass = 'bg-light text-danger';
                                    }
                                } else {
                                    $bgClass = 'border border-gray-300 bg-white text-black';
                                    $badgeClass = 'bg-success-subtle text-success';
                                }
                            @endphp

                            <div class="p-3 rounded {{ $bgClass }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold mb-1">{{ $schedule->subject }}</h6>
                                        <p class="mb-1">Hari: {{ ucfirst($schedule->day) }}</p>
                                        <p class="mb-0">
                                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}
                                            –
                                            {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                            @if ($schedule->jp_count > 1)
                                                <small class="d-block mt-1">({{ $schedule->jp_range }})</small>
                                            @endif
                                        </p>
                                    </div>

                                    <span class="badge {{ $badgeClass }}">
                                        Kelas: {{ $schedule->class }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="alert alert-info text-center">
                        Belum ada jadwal mengajar.
                    </div>
                @endforelse
            </div>

        </div>
    </div>
@endsection

@push('styles')
    <style>
        .bg-success-subtle {
            background-color: #d1e7dd !important;
            color: #0f5132 !important;
        }

        .badge {
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
        }
    </style>
@endpush
