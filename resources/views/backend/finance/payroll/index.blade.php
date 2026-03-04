@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
<x-section-page-mobile
    :label="$title"
    :icon="$icon"
/>
@endsection

@section('content')
@if ($payroll->count() == 0)
    <div class="card">
        <div class="card-body text-muted">
            Belum ada slip gaji
        </div>
    </div>
@else
    @foreach ($payroll as $p)
        <div class="card card-payroll mb-3" data-id="{{ $p->encrypted_id }}">
            <div class="card-body">
                <div class="d-flex mb-1">
                    <div>
                        <i class="ti ti-device-ipad-dollar" style="font-size: 20px;"></i>
                    </div>
                    <div class="ps-2" style="padding-top: 3px;">
                        <h6>{{ Common::monthFormat($p->months) . ' ' . $p->years }}</h6>
                    </div>
                    <div class="ms-auto text-info"><b>Rp. {{ number_format($p->total, 0, '', '.') }}</b></div>
                </div>

                <div class="d-flex" style="padding-bottom: 5px;border-bottom: 1px dashed #e2e6eb;">
                    <div>Gaji Pokok</div>
                    <div class="ms-auto">{{ number_format($p->salary, 0, '', '.') }}</div>
                </div>
                <div class="d-flex mt-2" style="padding-bottom: 5px;border-bottom: 1px dashed #e2e6eb;">
                    <div>Tunjangan</div>
                    <div class="ms-auto">{{ number_format($p->allowance, 0, '', '.') }}</div>
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $(".card-payroll").click(function() {
        let url = "{{ route('finance.payroll.show.slip', 0) }}"
        url = url.replace("0", $(this).data("id"))

        window.location = url
    })
})
</script>
@endpush
