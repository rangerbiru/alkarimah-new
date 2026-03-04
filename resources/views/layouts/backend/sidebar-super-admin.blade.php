@php
$set_branch = ($controller == 'branch') ? ' active' : '';
@endphp

<li class="slide">
    <a href="{{ route('branch.index') }}" class="side-menu__item{{ $set_branch }}">
        <span class=" side-menu__icon">
            <i class="bx bx-building"></i>
        </span>
        <span class="side-menu__label">Cabang</span>
    </a>
</li>
