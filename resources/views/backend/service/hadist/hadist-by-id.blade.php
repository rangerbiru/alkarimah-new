@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
    <x-section-page-mobile :label="$title" :icon="$icon" />
@endsection

@section('content')
    <div class="mt-3 min-vh-100">
        <div class="row">
            <div class="card shadow-sm">
                <form action="{{ route('service.hadistSearch', $slug) }}" method="GET" class="my-3">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Cari hadist..."
                            value="{{ request('q') }}">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </div>
                </form>
            </div>

            <div class="card shadow-sm pt-4">
                @foreach ($hadistData as $hadist)
                    <div class="card-hadist-body">
                        <h3 class="text-center">Hadist Ke-{{ $hadist['id'] }}</h3>
                        <p class="arab" style="text-align: right">
                            {{ $hadist['arab'] }}
                        </p>
                        <p style="text-align: justify; line-height: 1.60" class="fs-14">
                            {{ $hadist['terjemah'] }}
                        </p>
                        <hr>
                    </div>
                @endforeach
                <div class="d-flex justify-content-center mb-3 pagination">
                    {{ $hadistData->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>

            </div>
        </div>


    </div>
@endsection

<style>
    .card-hadist {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .card-hadist-body {
        padding: 0 15px;
        background-color: #fff;
    }

    .arab {
        font-size: 20px;
        line-height: 2;
        font-family: 'Amiri', serif;
    }

    @media (max-width: 576px) {
        .pagination {
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 0;
            gap: 5px;
        }

        .page-link {
            padding: 8px;
            font-size: 14px;
        }
    }
</style>
