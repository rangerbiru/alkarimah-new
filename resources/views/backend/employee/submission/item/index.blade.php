@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/submission/item" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-0 ">
            <div class="bg-white rounded-2">
                <div class="px-3 pt-3">
                    <form method="GET" action="{{ route('employee.submission.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Cari barang..."
                                value="{{ request('search') }}">
                            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>



            <div class="px-3 pb-3">
                <div class="d-flex align-items-center py-3 list-group-item">
                    <div class="me-3">
                        <div class="bg-success rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 30px; height: 30px;">
                            <i class="fas fa-box text-white"></i>
                        </div>
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>PWS</strong>
                                <div class="text-success fw-bold mt-1">
                                    {{-- {{ strtoupper($submission->employee?->task_main ?? 'UMUM') }} --}}
                                    umum
                                </div>
                            </div>
                            <div class="text-end">
                                {{-- @if ($submission->total_amount) --}}
                                <span class="text-dark fw-bold">Rp
                                    {{-- {{ number_format($submission->total_amount, 0, ',', '.') }}</span> --}}
                                    1000
                                    {{-- @endif --}}
                            </div>
                        </div>
                    </div>

                    {{-- <div class="ms-3">
                        @php
                            $statusClass = match ($submission->status) {
                                'approved' => 'bg-success',
                                'rejected' => 'bg-danger',
                                default => 'bg-info',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">
                            {{ strtoupper($submission->status) }}
                            approved
                        </span>
                    </div> --}}
                </div>
            </div>

            <div class="btn-floating">
                <a href="{{ route('employee.submission.item.create') }}" class="btn-primary">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
        </div>
    </div>
@endsection

<style>
    .btn-floating {
        position: fixed;
        bottom: 80px;
        right: 30px;
    }

    .btn-floating a {
        color: #fff;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .btn-floating i {
        font-size: 24px;
    }
</style>
