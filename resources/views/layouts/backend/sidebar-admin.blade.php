@php
    $set_user_bendahara = $controller == 'user' && request()->render == 'bendahara' ? ' active' : '';
    $set_user_kasir = $controller == 'user' && request()->render == 'kasir' ? ' active' : '';
    $set_user_kasirunit = $controller == 'user' && request()->render == 'kasir-unit' ? ' active' : '';
    $set_user_walikelas = $controller == 'user' && request()->render == 'wali-kelas' ? ' active' : '';
    $set_user_kepalasekolah = $controller == 'user' && request()->render == 'kepala-sekolah' ? ' active' : '';
    $set_user_penanngungjawab =
        $controller == 'user' && request()->render == 'penanggung-jawab-tabungan' ? ' active' : '';
@endphp

<li class="slide has-sub">
    <a href="javascript:void(0);" class="side-menu__item">
        <span class="side-menu__icon">
            <i class="bx bx-user"></i>
        </span>
        <span class="side-menu__label">{{ __('label.user') }}</span>
        <i class="fe fe-chevron-right side-menu__angle"></i>
    </a>

    <ul class="slide-menu child1">
        <li class="slide">
            <a href="{{ route('user.index', 'bendahara') }}" class="side-menu__item{{ $set_user_bendahara }}">
                {{ __('label.bendahara') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('user.index', 'kasir') }}" class="side-menu__item{{ $set_user_kasir }}">
                {{ __('label.kasir') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('user.index', 'kasir-unit') }}" class="side-menu__item{{ $set_user_kasirunit }}">
                {{ __('label.kasir_unit') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('user.index', 'kepala-sekolah') }}" class="side-menu__item{{ $set_user_kepalasekolah }}">
                {{ __('label.kepala_sekolah') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('user.index', 'wali-kelas') }}" class="side-menu__item{{ $set_user_walikelas }}">
                {{ __('label.wali_kelas') }}
            </a>
        </li>
        <li class="slide">
            <a href="{{ route('user.index', 'penanggung-jawab-tabungan') }}"
                class="side-menu__item{{ $set_user_penanngungjawab }}">
                {{ __('label.penanggung_jawab_tabungan') }}
            </a>
        </li>
    </ul>
</li>

@include('layouts.backend.sidebar-general', ['sections' => ['academic', 'hr', 'inventory']])

@include('layouts.backend.sidebar-menu-cancellation')
