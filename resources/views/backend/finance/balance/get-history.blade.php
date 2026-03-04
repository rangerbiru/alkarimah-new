@foreach ($transaction as $t)
    @php
    if ($t->transaction->is_paid) {
        $date = Common::dateFormat($t->transaction->paid_at, 'dd mmm yyyy, hh:ii WIB');
        $url = route('finance.balance.show', $t->encrypted_id);
    } else {
        $date = Common::dateFormat($t->transaction->created_at, 'dd mmm yyyy, hh:ii WIB');
        $url = route('finance.balance.waiting', $t->transaction->encrypted_id);
    }
    @endphp

    <div class="card-history" data-id="{{ $t->transaction->encrypted_id }}" data-idh="{{ $t->encrypted_id }}" data-status="{{ $t->transaction->status }}">
        <div class="d-flex align-items-center">
            <div class="icon me-2">
                <img src="{{ ($t->debit > 0) ? $t->transaction->method->image : asset('images/icons/min.png') }}" style="width: 40px;" />
            </div>
            <div class="text">
                {{ $t->transaction->number }}<br />
                <small class="text-muted">
                    {{ $date }}<br />
                    {{ $t->transaction->method->name }}

                    &nbsp;|&nbsp;
                    <a href="{{ $url }}" class="text-primary"><small>Detail <i class="fa-solid fa-angle-right"></i></small></a>
                </small>
            </div>
            <div class="text ms-auto text-end">
                <div class="mb-1">
                    @if ($t->debit > 0)
                        <span class="text-success">+{{ number_format($t->debit, 0, '', '.') }}</span>
                    @else
                        <span class="text-danger">-{{ number_format($t->credit, 0, '', '.') }}</span>
                    @endif
                </div>
                <div class="mb-1">
                    {!! $t->transaction->status_badge !!}
                </div>
            </div>
        </div>
    </div>
@endforeach
