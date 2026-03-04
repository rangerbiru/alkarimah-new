@php
$route = Str::of(Route::currentRouteName())->explode('.');
$controller = $route['0'];
$action = @$route['1'];
$function = @$route['2'];

$set_bill = ($function == 'index') ? ' active' : '';
$set_history = ($function == 'history') ? ' active' : '';
@endphp

<div class="row gx-0">
    <div class="col">
        <div class="d-grid">
            <a href="{{ route('finance.payment.index') }}" class="tab{{ $set_bill }}">
                {{ __('label.bill') }}
            </a>
        </div>
    </div>
    <div class="col">
        <div class="d-grid">
            <a href="{{ route('finance.payment.history') }}" class="tab{{ $set_history }}">
                {{ __('label.history') }}
            </a>
        </div>
    </div>
</div>
