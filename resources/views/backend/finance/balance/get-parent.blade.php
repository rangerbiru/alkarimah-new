@if (empty($parent))
    <div class="text-center mb-4">
        <img src="{{ asset('images/vectors/no-data-found.png') }}" class="img-fluid" style="height: 230px;" />
        <h6 class="fw-normal text-muted mt-3" style="line-height: 23px;">
            <b>Data tidak Ditemukan</b><br />
            Orang Tua yang Anda cari tidak ditemukan di dalam sistem.
        </h6>
    </div>
@else
    <div class="d-flex align-items-center">
        <div class="me-3">
            <i class="bx bxs-user-circle text-primary" style="font-size: 60px;"></i>
        </div>
        <div>
            <h5 class="text-grey mb-1">{{ $parent->name }}</h5>
            <h6 class="text-muted fw-normal mb-0">
                <i class="bx bx-phone me-1"></i> {{ Common::phoneFormat($parent->phone) }}
            </h6>
        </div>
        <div class="ms-auto text-end">
            <div class="mb-1 fw-bold text-grey">
                {{ __('label.balance') }}
            </div>
            <h4 class="text-success mb-0 balance">
                {{ 'Rp. ' . number_format($parent->balance, 0, ',', '.') }}
            </h4>
        </div>
    </div>

    <div class="card mt-3 mb-0">
        <div class="card-body p-3 bg-light">
            <div class="row">
                <div class="col-sm-6">
                    <table class="table-padding">
                        <tr>
                            <td class="fw-bold" style="width: 140px;">{{ __('label.parent_name') }}</td>
                            <td class="divide">:</td>
                            <td>{{ $parent->name }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">{{ __('label.phone_number') }}</td>
                            <td class="divide">:</td>
                            <td>{{ Common::phoneFormat($parent->phone) }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-sm-6">
                    <table class="table-padding">
                        <tr>
                            <td class="fw-bold" style="width: 140px;">Email</td>
                            <td class="divide">:</td>
                            <td>{{ $parent->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Alamat</td>
                            <td class="divide">:</td>
                            <td>{{ $parent->address ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif
