<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="gradient"
    data-menu-styles="light">

<head>
    <meta charset="UTF-8" />
    <meta name='viewport' content='width=device-width, initial-scale=1.0' />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="Author" content="Ponpes Al-Karimah" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ Config::get('app.name') }}</title>

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/apple-touch-icon.png') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon/favicon-16x16.png') }}" />
    <link rel="manifest" href="{{ asset('images/favicon/site.webmanifest') }}" />

    <link id="style" href="{{ asset('vendors/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('vendors/sweetalert/sweetalert2.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('vendors/fontawesome/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('vendors/tabler/tabler-icons.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/icons.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/velvet.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/app.css') }}" rel="stylesheet" />

    @stack('styles')

    <script src="{{ asset('js/jquery-3.2.1.min.js') }}"></script>
    <script>
        const label_search = "{{ __('label.search') }}"
        const label_edit = "{{ __('label.edit') }}"
        const label_delete = "{{ __('label.delete') }}"
        const label_success = "{{ strtoupper(__('label.success')) }}"
        const label_failed = "{{ strtoupper(__('label.failed')) }}"
        const label_info = "{{ strtoupper(__('label.info')) }}"
        const label_confirmation = "{{ strtoupper(__('label.confirmation')) }}"
        const label_yes = "{{ strtoupper(__('label.yes')) }}"
        const label_cancel = "{{ strtoupper(__('label.cancel')) }}"
        const label_choose = "{{ __('label.choose') }}"
        const label_nodata = "{{ __('string.no_data_available') }}"
        const string_confirm_delete = "{{ __('string.confirm_delete') }}"
        const month_mmmm = JSON.parse('{!! json_encode([
            __('label.january'),
            __('label.february'),
            __('label.march'),
            __('label.april'),
            __('label.may'),
            __('label.june'),
            __('label.july'),
            __('label.august'),
            __('label.september'),
            __('label.october'),
            __('label.november'),
            __('label.december'),
        ]) !!}')
        const month_mmm = JSON.parse('{!! json_encode([
            __('label.jan'),
            __('label.feb'),
            __('label.mar'),
            __('label.apr'),
            __('label.may'),
            __('label.jun'),
            __('label.jul'),
            __('label.aug'),
            __('label.sep'),
            __('label.oct'),
            __('label.nov'),
            __('label.dec'),
        ]) !!}')
        const day_dddd = JSON.parse('{!! json_encode([
            __('label.monday'),
            __('label.tuesday'),
            __('label.wednesday'),
            __('label.thursday'),
            __('label.friday'),
            __('label.saturday'),
            __('label.sunday'),
        ]) !!}')
        const day_ddd = JSON.parse('{!! json_encode([
            __('label.mon'),
            __('label.tue'),
            __('label.wed'),
            __('label.thu'),
            __('label.fri'),
            __('label.sat'),
            __('label.sun'),
        ]) !!}')
    </script>
</head>

<body>
    @php
        $route = Str::of(Route::currentRouteName())->explode('.');
        $controller = $route['0'];
        $action = @$route['1'];
        $function = @$route['2'];
        $function2 = @$route['3'];
    @endphp

    <div id="loader">
        <img src="{{ asset('images/loader.gif') }}" style="height: 75px;" />
    </div>

    <div class="page">
        <header class="app-header">
            <div class="main-header-container container-fluid">
                @include('layouts.backend.header')
            </div>
        </header>

        <aside class="app-sidebar" id="sidebar">
            @include('layouts.backend.sidebar')
        </aside>

        <div class="d-sm-flex d-block align-items-center justify-content-between page-header-breadcrumb">
            @yield('header')
        </div>

        <div class="main-content app-content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </div>

        <footer class="footer mt-auto py-3 bg-white">
            <div class="container">
                <div class="clearfix">
                    <div class="float-end">Image by <a href="https://www.freepik.com">freepik.com</a></div>

                    <span class="text-muted">Copyright &copy; {{ date('Y') }} </span><b>Si-Alka (Sistem Informasi
                        Al Karimah)</b>
                </div>
            </div>
        </footer>
    </div>

    <div class="scrollToTop">
        <span class="arrow"><i class="ri-arrow-up-circle-fill fs-20"></i></span>
    </div>
    <div id="responsive-overlay"></div>

    <script src="{{ asset('vendors/popperjs/core/umd/popper.min.js') }}"></script>
    <script src="{{ asset('vendors/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendors/sweetalert/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('vendors/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('vendors/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('js/simplebar.js') }}"></script>
    <script src="{{ asset('js/defaultmenu.js') }}"></script>
    {{-- <script src="{{ asset('js/sticky.js') }}"></script> --}}
    <script src="{{ asset('js/velvet.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>

    @if (session()->has('success'))
        <script>
            $(document).ready(function() {
                setNotifSuccess("{{ session()->get('success') }}", false)
            })
        </script>
    @endif

    @if (session()->has('error'))
        <script>
            $(document).ready(function() {
                setNotifInfo("{{ session()->get('error') }}")
            })
        </script>
    @endif

    @stack('scripts')
</body>

</html>
