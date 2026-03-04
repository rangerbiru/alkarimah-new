<div class="main-sidebar-header">
    <a href="{{ route('dashboard.index') }}" class="header-logo">
        <img src="{{ asset('images/logo-text.png') }}" alt="logo" class="desktop-logo">
        <img src="{{ asset('images/logo.png') }}" alt="logo" class="toggle-logo">
        <img src="{{ asset('images/logo-text.png') }}" alt="logo" class="desktop-dark">
        <img src="{{ asset('images/logo.png') }}" alt="logo" class="toggle-dark">
    </a>
</div>

<div class="main-sidebar" id="sidebar-scroll">
    <nav class="main-menu-container nav nav-pills flex-column sub-open">
        <div class="slide-left" id="slide-left">
            <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
                <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path>
            </svg>
        </div>
        <ul class="main-menu">
            <li class="slide__category"><span class="category-name">{{ __('string.hi_welcome') }}</span></li>

            @php
                $set_dashboard = $controller == 'dashboard' ? ' active' : '';
                $set_absensi = $controller == 'employee.tahfidz.index' ? ' active' : '';
            @endphp
            @if (Auth::user()->role->value == 'pegawai')
                <li class="slide">
                    <a href="{{ route('dashboard.index') }}" class="side-menu__item{{ $set_dashboard }}">
                        <span class=" side-menu__icon">
                            <i class="bx bx-bar-chart-alt-2"></i>
                        </span>
                        <span class="side-menu__label">Dashboard</span>
                    </a>
                </li>

                <li class="slide">
                    <a href="{{ route('employee.tahfidz.index') }}" class="side-menu__item{{ $set_absensi }}">
                        <span class=" side-menu__icon">
                            <i class="bx bx-book-reader"></i>
                        </span>
                        <span class="side-menu__label">Absensi Tahfidz</span>
                    </a>
                </li>
            @endif

            @php
                $menu_groups = ['none', 'academic', 'finance'];
            @endphp

            @foreach ($menu_groups as $mg)
                @if ($mg != 'none' && !empty($data->sidebar[$mg]))
                    <li class="slide__category"><span class="category-name">{{ __('label.' . $mg) }}</span></li>
                @endif

                @foreach ($data->sidebar[$mg] as $menu)
                    @php
                        $menu_active = '';

                        if (empty($menu->route)) {
                            $route = 'javascript:void(0);';
                        } else {
                            $menu_route = explode('.', $menu->route->name);

                            if (count($menu_route) == 2) {
                                $menu_active = $menu_route[0] == $controller ? ' active' : '';
                            } elseif (count($menu_route) == 3) {
                                $menu_active =
                                    $menu_route[0] == $controller && $menu_route[1] == $action ? ' active' : '';
                            } else {
                                $menu_active =
                                    $menu_route[0] == $controller &&
                                    $menu_route[1] == $action &&
                                    $menu_route[2] == $function
                                        ? ' active'
                                        : '';
                            }

                            $route = empty($menu->route->params)
                                ? route($menu->route->name)
                                : route($menu->route->name, (array) $menu->route->params);
                        }
                    @endphp

                    @if (empty($menu->child))
                        <li class="slide">
                            <a href="{{ $route }}" class="side-menu__item{{ $menu_active }}">
                                <span class=" side-menu__icon">
                                    <i class="{{ $menu->icon }}"></i>
                                </span>
                                <span class="side-menu__label">{{ $menu->name }}</span>
                            </a>
                        </li>
                    @else
                        <li class="slide has-sub">
                            <a href="javascript:void(0);" class="side-menu__item">
                                <span class="side-menu__icon">
                                    <i class='{{ $menu->icon }}'></i>
                                </span>
                                <span class="side-menu__label">{{ $menu->name }}</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0)">{{ $menu->name }}</a>
                                </li>

                                @foreach ($menu->child as $c)
                                    @php
                                        $menu_route = explode('.', $c->route->name);

                                        if (empty($c->route->params)) {
                                            $route = route($c->route->name);

                                            if (count($menu_route) == 2) {
                                                $menu_active = $menu_route[0] == $controller ? ' active' : '';
                                            } elseif (count($menu_route) == 3) {
                                                $menu_active =
                                                    $menu_route[0] == $controller && $menu_route[1] == $action
                                                        ? ' active'
                                                        : '';
                                            } else {
                                                $menu_active =
                                                    $menu_route[0] == $controller &&
                                                    $menu_route[1] == $action &&
                                                    $menu_route[2] == $function
                                                        ? ' active'
                                                        : '';
                                            }
                                        } else {
                                            $route = route($c->route->name, (array) $c->route->params);
                                        }
                                    @endphp

                                    <li class="slide">
                                        <a href="{{ $route }}" class="side-menu__item{{ $menu_active }}">
                                            {{ $c->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach
            @endforeach
        </ul>

        <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24"
                height="24" viewBox="0 0 24 24">
                <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path>
            </svg></div>
    </nav>
</div>
