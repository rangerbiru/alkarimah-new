{{-- Parent shortcuts on the employee dashboard, for a dual-role user (employee + parent) --}}
<div class="card mt-4">
    <div class="card-body">
        <h6 class="title-line">{{ __('label.parent_menu') }}</h6>

        <div class="row mt-3">
            <div class="col-3 text-center">
                <a href="{{ route('finance.payment.index') }}" class="btn-icon text-grey">
                    <img src="{{ asset('images/icons/pembayaran.svg') }}" />

                    <div class="mt-1 text">{{ __('label.payment') }}</div>
                </a>
            </div>
            <div class="col-3 text-center">
                <a href="{{ route('finance.balance.index') }}" class="btn-icon text-grey">
                    <img src="{{ asset('images/icons/topup-saldo.svg') }}" />

                    <div class="mt-1 text">{{ __('label.topup_balance') }}</div>
                </a>
            </div>
            <div class="col-3 text-center">
                <a href="{{ route('finance.savings.index') }}" class="btn-icon text-grey">
                    <img src="{{ asset('images/icons/tabungan.svg') }}" />

                    <div class="mt-1 text">{{ __('label.savings') }}</div>
                </a>
            </div>
            <div class="col-3 text-center">
                <a href="{{ route('academic.student.children') }}" class="btn-icon text-grey">
                    <img src="{{ asset('images/icons/siswa.svg') }}" />

                    <div class="mt-1 text">{{ __('label.my_children') }}</div>
                </a>
            </div>
        </div>
    </div>
</div>
