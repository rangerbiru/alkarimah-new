@foreach ($transaction as $t)
    @php
    if ($t->is_paid) {
        $date = Common::dateFormat($t->paid_at, 'dd mmm yyyy, hh:ii WIB');
        $url = route('finance.payment.show', $t->encrypted_id);
    } else {
        $date = Common::dateFormat($t->created_at, 'dd mmm yyyy, hh:ii WIB');
        $url = route('finance.payment.waiting', $t->encrypted_id);
    }
    @endphp

    <div class="card-history" data-id="{{ $t->encrypted_id }}" data-status="{{ $t->status }}">
        <div class="d-flex">
            <div class="icon me-2">
                <img src="{{ $t->method->image }}" style="width: 40px;" />
            </div>
            <div class="text">
                {{ $t->number }}

                <div style="line-height: 14px;">
                    <small class="text-muted">
                        <i class="ti ti-calendar"></i> {{ $date }}<br />
                        <i class="ti ti-credit-card"></i> {{ $t->method->name }}

                        &nbsp;|&nbsp;
                        <a href="{{ $url }}" class="text-primary"><small>Detail <i class="fa-solid fa-angle-right"></i></small></a>
                    </small>
                </div>
            </div>
            <div class="text ms-auto text-end">
                <div class="mb-1">
                    <span>Rp. {{ number_format($t->total, 0, '', '.') }}</span>
                </div>
                <div class="mb-1">
                    {!! $t->status_badge !!}
                </div>
            </div>
        </div>
    </div>
@endforeach
