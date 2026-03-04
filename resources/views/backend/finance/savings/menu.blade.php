@php
$route = Str::of(Route::currentRouteName())->explode('.');
$controller = $route['0'];
$action = @$route['1'];
$function = @$route['2'];

$set_savings = ($function == 'index') ? ' active' : '';
$set_history = ($function == 'history') ? ' active' : '';
@endphp

<div class="row gx-0">
    <div class="col">
        <div class="d-grid">
            <a href="{{ route('finance.savings.index') }}" class="tab{{ $set_savings }}">
                {{ __('label.savings') }}
            </a>
        </div>
    </div>
    <div class="col">
        <div class="d-grid">
            <a href="{{ route('finance.savings.history') }}" class="tab{{ $set_history }}">
                {{ __('label.history') }}
            </a>
        </div>
    </div>
</div>
