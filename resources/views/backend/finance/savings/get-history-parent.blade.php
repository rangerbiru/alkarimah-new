@foreach ($mutation as $m)
    @if ($m->is_deposit)
        @php
        if ($m->transaction->is_paid) {
            $date = Common::dateFormat($m->transaction->paid_at, 'dd mmm yyyy, hh:ii WIB');
            $url = route('finance.savings.show', $m->transaction->encrypted_id);
        } else {
            $date = Common::dateFormat($m->transaction->created_at, 'dd mmm yyyy, hh:ii WIB');
            $url = route('finance.savings.waiting', $m->transaction->encrypted_id);
        }
        @endphp

        <div class="card-history" data-id="{{ $m->transaction->encrypted_id }}" data-status="{{ $m->transaction->status }}" data-flag="1">
            <div class="d-flex">
                <div class="icon me-2">
                    <img src="{{ $m->transaction->method->image }}" style="width: 40px;" />
                </div>
                <div class="text">
                    {{ $m->transaction->number }}

                    <div style="line-height: 14px;">
                        <small class="text-muted">
                            <i class="ti ti-calendar"></i> {{ $date }}<br />
                            <i class="ti ti-credit-card"></i> {{ $m->transaction->method->name }}

                            &nbsp;|&nbsp;
                            <a href="{{ $url }}" class="text-primary"><small>Detail <i class="fa-solid fa-angle-right"></i></small></a>
                        </small>
                    </div>
                </div>
                <div class="text ms-auto text-end">
                    <div class="mb-1">
                        <span>Rp. {{ number_format($m->transaction->total, 0, '', '.') }}</span>
                    </div>
                    <div class="mb-1">
                        {!! $m->transaction->status_badge !!}
                    </div>
                </div>
            </div>
        </div>
    @else
        @php
        if ($m->withdrawal->is_processed) {
            $date = Common::dateFormat($m->withdrawal->processed_at, 'dd mmm yyyy, hh:ii WIB');
            $url = route('finance.savings.show-withdrawal', $m->withdrawal->encrypted_id);
        } else {
            $date = Common::dateFormat($m->withdrawal->created_at, 'dd mmm yyyy, hh:ii WIB');
            $url = route('finance.savings.waiting', $m->withdrawal->encrypted_id);
        }
        @endphp

        <div class="card-history" data-id="{{ $m->withdrawal->encrypted_id }}" data-flag="2">
            <div class="d-flex">
                <div class="icon me-2">
                    <img src="{{ asset('images/icons/min.png') }}" style="width: 40px;" />
                </div>
                <div class="text">
                    {{ $m->withdrawal->number }}

                    <div style="line-height: 14px;">
                        <small class="text-muted">
                            <i class="ti ti-calendar"></i> {{ $date }}<br />
                            <i class="ti ti-user"></i> {{ $m->withdrawal->creator->name }}

                            &nbsp;|&nbsp;
                            <a href="{{ $url }}" class="text-primary"><small>Detail <i class="fa-solid fa-angle-right"></i></small></a>
                        </small>
                    </div>
                </div>
                <div class="text ms-auto text-end">
                    <div class="mb-1">
                        <span>Rp. {{ number_format($m->withdrawal->total, 0, '', '.') }}</span>
                    </div>
                    <div class="mb-1">
                        {!! $m->withdrawal->status_badge !!}
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
