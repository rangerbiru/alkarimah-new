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

        <div class="main-header-center header-link text-white" style="padding-top: 1rem;">
            <b>Ahlan Wa Sahlan</b>
        </div>
    </div>
</div>
<div class="header-content-right">
    @foreach ($data->header as $menu)
        @php
            $menu_active = '';

            if (empty($menu->route)) {
                $route = 'javascript:void(0);';
            } else {
                $menu_route = explode('.', $menu->route->name);

                if (count($menu_route) == 2) {
                    $menu_active = $menu_route[0] == $controller ? ' active' : '';
                } else {
                    $menu_active = $menu_route[0] == $controller && $menu_route[1] == $action ? ' active' : '';
                }

                $route = empty($menu->route->params)
                    ? route($menu->route->name)
                    : route($menu->route->name, (array) $menu->route->params);
            }
        @endphp

        @if (empty($menu->child))
            <div class="header-element">
                <a aria-label="anchor" href="{{ $route }}" class="header-link data-bs-auto-close="outside">
                    <i class="{{ $menu->icon }} header-link-icon"></i>&nbsp;
                    {{ $menu->name }}
                </a>
            </div>
        @else
            <div class="header-element">
                <a href="javascript:void(0);" class="header-link header-link-icon-name dropdown-toggle"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    {{-- <span class=""><i class="{{ $menu->icon }} header-link-icon"></i>{{ $menu->name }}</span> --}}
                    <div class="d-flex align-items-center">
                        <i class="{{ $menu->icon }}"></i>{{ $menu->name }}
                    </div>
                </a>

                <ul class="main-header-dropdown dropdown-menu border-0" data-popper-placement="none">
                    @foreach ($menu->child as $c)
                        @php
                            $menu_route = explode('.', $c->route->name);

                            if (empty($c->route->params)) {
                                $route = route($c->route->name);

                                if (count($menu_route) == 2) {
                                    $menu_active = $menu_route[0] == $controller ? ' active' : '';
                                } else {
                                    $menu_active =
                                        $menu_route[0] == $controller && $menu_route[1] == $action ? ' active' : '';
                                }
                            } else {
                                $route = route($c->route->name, (array) $c->route->params);
                            }
                        @endphp

                        <li>
                            <a class="dropdown-item d-flex align-items-center{{ $menu_active }}"
                                href="{{ $route }}">
                                {{ $c->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endforeach

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
            <li><a class="dropdown-item border-bottom" href={{ route('user.profile') }}><i
                        class="fs-13 me-2 bx bx-user"></i>Profile</a></li>
            <li><a class="dropdown-item border-bottom" href={{ route('user.password') }}><i
                        class="fs-13 me-2 bx bx-lock"></i>Change Password</a></li>
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
