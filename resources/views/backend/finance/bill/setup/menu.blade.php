@php
$route = Str::of(Route::currentRouteName())->explode('.');
$action = $route['2'];
$function = @$route['3'];

$set_index = ($function == 'index') ? ' active' : '';
$set_setting = ($function == 'setting') ? ' active' : '';
$set_list = ($action == 'index') ? ' active' : '';
@endphp

<ul class="nav nav-pills nav-justified d-sm-flex d-block mb-2" role="tablist">
    <li class="nav-item" role="presentation">
        <a href="{{ route('finance.bill.setup.index') }}" class="nav-link{{ $set_index }}" role="tab" tabindex="-1">
            <div class="d-flex">
                <div class="me-2"><i class="bx bx-book-content"></i></div>
                <div>
                    <b>{{ __('label.bill_list') }}</b><br />
                    <small>{{ __('string.bill_manage') }}</small>
                </div>
            </div>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a href="{{ route('finance.bill.setup.setting') }}" class="nav-link{{ $set_setting }}" role="tab" tabindex="-1">
            <div class="d-flex">
                <div class="me-2"><i class="bx bx-cog"></i></div>
                <div>
                    <b>{{ __('label.bill_setting') }}</b><br />
                    <small>{{ __('string.bill_setting') }}</small>
                </div>
            </div>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a href="{{ route('finance.bill.index') }}" class="nav-link{{ $set_list }}" role="tab" tabindex="-1">
            <div class="d-flex">
                <div class="me-2"><i class="bx bx-user-pin"></i></div>
                <div>
                    <b>{{ __('label.bill_student') }}</b><br />
                    <small>{{ __('string.bill_detail') }}</small>
                </div>
            </div>
        </a>
    </li>
</ul>
