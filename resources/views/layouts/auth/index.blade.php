<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="gradient"
    data-menu-styles="dark">

<head>
    <meta charset="UTF-8" />
    <meta name='viewport' content='width=device-width, initial-scale=1.0' />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="Author" content="Ponpes Al-Karimah" />
    <meta name="robots" content="noindex,nofollow" />

    <title>{{ Config::get('app.name') }}</title>

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/apple-touch-icon.png') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon/favicon-16x16.png') }}" />
    <link rel="manifest" href="{{ asset('images/favicon/site.webmanifest') }}" />

    <link id="style" href="{{ asset('vendors/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('vendors/sweetalert/sweetalert2.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('vendors/fontawesome/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('css/icons.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/velvet.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/auth.css') }}" rel="stylesheet" />

    @stack('styles')

    <style>
        .btn-loader i {
            top: 0.55rem;
        }
    </style>

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
    <div class="page error-bg" id="particles-js">
        <div class="error-page">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-8 col-md-12 col-sm-10">
                        <div class="card custom-card mb-0 cover-background overflow-hidden rounded-end rounded-0 d-block d-sm-none"
                            style="height: 250px;">
                            <div class="card-img-overlay d-flex align-items-center p-0 rounded-0">
                                <div class="card-body p-3 text-center rectangle3">
                                    <div>
                                        <a href="{{ route('base') }}">
                                            <img src="{{ asset('images/logo.png') }}" style="width: 100px;"
                                                alt="logo" class="desktop-dark">
                                        </a>
                                    </div>
                                    <h5 class="mt-4 op-9 text-fixed-white">Sistem Informasi Al Karimah
                                    </h5>
                                    <h4 class="op-9 text-fixed-white">Si-Alka</h4>

                                    <small class="text-fixed-white">Mandungan - Karanganyar, Jawa Tengah 57713</small>

                                </div>
                            </div>
                        </div>

                        <div class="card custom-card rectangle2">
                            <div class="card-body p-0">
                                <div class="row">
                                    <div class="col-xl-6 col-md-6 pe-sm-0">
                                        <div class="p-sm-5 p-3">
                                            @yield('content')
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-md-6 ps-0 text-fixed-white rounded-0 d-none d-md-block  ">
                                        <div
                                            class="card custom-card mb-0 cover-background overflow-hidden rounded-end rounded-0">
                                            <div class="card-img-overlay d-flex  align-items-center p-0 rounded-0">
                                                <div class="card-body p-5 rectangle3">
                                                    <div>
                                                        <a href="{{ route('base') }}">
                                                            <img src="{{ asset('images/logo.png') }}"
                                                                style="width: 100px;" alt="logo"
                                                                class="desktop-dark">
                                                        </a>
                                                    </div>
                                                    <h5 class="mt-4 op-9 text-fixed-white">Sistem Informasi Al Karimah
                                                    </h5>
                                                    <h3 class="op-9 text-fixed-white">Si-Alka</h3>

                                                    <small class="text-fixed-white">Mandungan - Karanganyar, Jawa Tengah
                                                        57713</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendors/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendors/sweetalert/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>

    @if (session()->has('success'))
        <script>
            $(document).ready(function() {
                setNotifSuccess("{{ session()->get('success') }}", false)
            })
        </script>
    @endif

    @stack('scripts')
</body>

</html>
