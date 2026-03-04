@php
$route = Str::of(Route::currentRouteName())->explode('.');
$controller = $route['0'];
$action = @$route['1'];
$function = @$route['2'];

$set_topup = ($function == 'index') ? ' active' : '';
$set_history = ($function == 'history') ? ' active' : '';
@endphp

<div class="row gx-0">
    <div class="col">
        <div class="d-grid">
            <a href="{{ route('finance.balance.index') }}" class="tab{{ $set_topup }}">
                {{ __('label.topup') }}
            </a>
        </div>
    </div>
    <div class="col">
        <div class="d-grid">
            <a href="{{ route('finance.balance.history') }}" class="tab{{ $set_history }}">
                {{ __('label.history') }}
            </a>
        </div>
    </div>
</div>
