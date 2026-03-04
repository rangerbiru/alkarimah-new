<div class="header-content-left">
    <div class="header-element">
        <div class="horizontal-logo">
            <a href="{{ route('dashboard.index') }}" class="header-logo">
                <img src="{{ asset('images/logo-text.png') }}" alt="logo" class="desktop-logo">
                <img src="{{ asset('images/logo.png') }}" alt="logo" class="toggle-logo">
                <img src="{{ asset('images/logo-text.png') }}" alt="logo" class="desktop-dark">
                <img src="{{ asset('images/logo.png') }}" alt="logo" class="toggle-dark">
            </a>
        </div>
    </div>

    <div class="header-element">
        <a aria-label="anchor" href="javascript:void(0);" class="sidemenu-toggle header-link" data-bs-toggle="sidebar">
            <span class="open-toggle me-2">
                <i class="bx bx-menu header-link-icon"></i>
            </span>
        </a>

        {{-- @if (Auth::user()->role != $data->user->role->SuperAdmin)
            <div class="main-header-center d-none d-lg-block header-link text-white" style="padding-top: 1.4rem;">
                <i class="fa-solid fa-building fa-lg"></i> &nbsp;{{ Auth::user()->branch->name }}
            </div>
        @endif --}}

        {{-- @if (Auth::user()->role != $data->user->role->SuperAdmin)
            @if (Auth::user()->branch)
                <div class="main-header-center d-none d-lg-block header-link text-white" style="padding-top: 1.4rem;">
                    <i class="fa-solid fa-building fa-lg"></i> &nbsp;{{ Auth::user()->branch->name }}
                </div>
            @endif
        @endif --}}

        @if (Auth::user()->role != optional(optional($data->user)->role)->SuperAdmin)
            @if (Auth::user()->branch)
                <div class="main-header-center d-none d-lg-block header-link text-white" style="padding-top: 1.4rem;">
                    <i class="fa-solid fa-building fa-lg"></i> &nbsp;{{ Auth::user()->branch->name }}
                </div>
            @endif
        @endif


    </div>
</div>
<div class="header-content-right">
    @if (Auth::user()->is_admin)
        <div class="header-element">
            <a href="javascript:void(0);" class="header-link header-link-icon-name dropdown-toggle"
                data-bs-toggle="dropdown" data-bs-auto-close="outside">
                <div class="d-flex align-items-center">
                    <i class="bx bx-cog"></i>Pengaturan
                </div>
            </a>

            <ul class="main-header-dropdown dropdown-menu border-0" data-popper-placement="none">
                <li>
                    <a href="{{ route('setting.year.index') }}" class="dropdown-item d-flex align-items-center">
                        Tahun Ajaran
                        </ </ul>
        </div>
    @elseif (Auth::user()->is_kasir)
        <div class="header-element">
            <a href="{{ route('setting.index') }}" class="header-link header-link-icon-name"
                data-bs-auto-close="outside">
                <div class="d-flex align-items-center">
                    <i class="bx bx-cog"></i>Pengaturan
                </div>
            </a>
        </div>
    @endif

    <div class="header-element mainuserProfile">
        <!-- Start::header-link|dropdown-toggle -->
        <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile"
            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
            <div class="d-flex align-items-center">
                <div class="d-sm-flex wd-100p">
                    <div class="avatar avatar-sm">
                        <img alt="avatar" class="rounded-circle" src="{{ Auth::user()->photo }}"
                            style="border: 2px solid white;" />
                    </div>
                    <div class="ms-2 my-auto d-none d-xl-flex">
                        <h6 class=" font-weight-semibold mb-0 fs-13 user-name d-sm-block d-none">
                            {{ Auth::user()->name }}
                            <i class="bx bx-chevron-down"></i>
                        </h6>
                    </div>
                </div>
            </div>
        </a>
        <!-- End::header-link|dropdown-toggle -->
        <ul class="dropdown-menu  border-0 main-header-dropdown  overflow-hidden header-profile-dropdown"
            aria-labelledby="mainHeaderProfile">
            <li><a class="dropdown-item border-bottom" href={{ route('user.profile') }}>
                    <i class="fs-13 me-2 bx bx-user"></i>{{ __('label.profile') }}</a>
            </li>
            <li>
                <a class="dropdown-item border-bottom" href={{ route('user.password') }}>
                    <i class="fs-13 me-2 bx bx-lock"></i>{{ __('label.change_password') }}
                </a>
            </li>
            {{-- change password --}}
            <li>
                <a class="dropdown-item" href="{{ route('auth.logout') }}">
                    <i class="fs-13 me-2 bx bx-arrow-to-right"></i>{{ __('label.logout') }}
                </a>
            </li>
        </ul>
    </div>
    <div class="header-element header-fullscreen">
        <a aria-label="anchor" onclick="openFullscreen();" href="javascript:void(0);" class="header-link">
            <i class="bx bx-fullscreen header-link-icon  full-screen-open"></i>
            <i class="bx bx-exit-fullscreen header-link-icon  full-screen-close  d-none"></i>
        </a>
    </div>
</div>
