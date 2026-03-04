@if (empty($student))
    <div id="start" class="text-center mb-4">
        <img src="{{ asset('images/vectors/no-data-found.png') }}" class="img-fluid" style="height: 230px;" />
        <h6 class="fw-normal text-muted mt-3" style="line-height: 23px;">
            <b>Data tidak Ditemukan</b><br />
            Siswa yang Anda cari tidak ditemukan
        </h6>
    </div>
@else
    <div class="d-flex align-items-center">
        <div class="me-1">
            <img src="{{ asset('images/icons/student-outline.png') }}" style="height: 60px;" />
        </div>
        <div>
            <h5 class="text-grey mb-1">{{ $student->name }}</h5>
            <h6 class="text-muted fw-normal mb-0">{{ __('label.nis') . ' : ' . $student->nis }}</h6>
        </div>
        <div class="ms-auto text-end">
            <div class="mb-1 fw-bold text-grey">
                {{ __('label.savings_balance') }}
            </div>
            <h4 class="text-success mb-0 balance">
                {{ 'Rp. ' . number_format($student->balance_savings, 0, '', '.') }}
            </h4>
        </div>
    </div>

    <div class="card mt-3 mb-0">
        <div class="card-body p-3 bg-light">
            <div class="row">
                <div class="col-sm-6">
                    <table class="table-padding">
                        <tr>
                            <td class="fw-bold" style="width: 140px;">{{ __('label.level_education') }}</td>
                            <td class="divide">:</td>
                            <td>{{ strtoupper($student->class->level_education->value) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">{{ __('label.class') }}</td>
                            <td class="divide">:</td>
                            <td>{{ $student->class->name }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-sm-6">
                    <table class="table-padding">
                        <tr>
                            <td class="fw-bold" style="width: 140px;">{{ __('label.parent_name') }}</td>
                            <td class="divide">:</td>
                            <td>{{ $student->parent->name }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">{{ __('label.phone_number') }}</td>
                            <td class="divide">:</td>
                            <td>{{ Common::phoneFormat($student->parent->phone) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif
