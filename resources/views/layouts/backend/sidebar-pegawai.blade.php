@if (Auth::user()->role->value == 'pegawai')
    <li class="slide">
        <a href="{{ route('employee.tahfidz.index') }}" class="side-menu__item">
            <span class=" side-menu__icon">
                <i class="bx bx-book-reader"></i>
            </span>
            <span class="side-menu__label">Absensi Tahfidz</span>
        </a>
    </li>
@endif
