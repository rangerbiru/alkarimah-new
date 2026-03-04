<div class="main-sidebar-header">
    <a href="{{ route('dashboard.index') }}" class="header-logo">
        <img src="{{ asset('images/logo-text.png') }}" alt="logo" class="desktop-logo">
        <img src="{{ asset('images/logo.png') }}" alt="logo" class="toggle-logo">
        <img src="{{ asset('images/logo-text.png') }}" alt="logo" class="desktop-dark">
        <img src="{{ asset('images/logo.png') }}" alt="logo" class="toggle-dark">
    </a>
</div>

@php
$set_dashboard = ($controller == 'dashboard') ? ' active' : '';
@endphp

<div class="main-sidebar" id="sidebar-scroll">
    <nav class="main-menu-container nav nav-pills flex-column sub-open">
        <div class="slide-left" id="slide-left">
            <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> </svg>
        </div>
        <ul class="main-menu">
            <li class="slide__category"><span class="category-name">{{ __('string.hi_welcome') }}</span></li>

            <li class="slide">
                <a href="{{ route('dashboard.index') }}" class="side-menu__item{{ $set_dashboard }}">
                    <span class=" side-menu__icon">
                        <i class="bx bx-bar-chart-alt-2"></i>
                    </span>
                    <span class="side-menu__label">Dashboard</span>
                </a>
            </li>

            @include('layouts.backend.sidebar-' . Auth::user()->role->value)
        </ul>
    </nav>
</div>
