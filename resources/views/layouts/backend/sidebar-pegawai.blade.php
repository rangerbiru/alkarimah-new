@if (in_array(Auth::user()->role->value, ['pegawai', 'kepala-sekolah']))
    <li class="slide">
        <a href="{{ route('employee.tahfidz.index') }}" class="side-menu__item">
            <span class=" side-menu__icon">
                <i class="bx bx-book-reader"></i>
            </span>
            <span class="side-menu__label">Absensi Tahfidz</span>
        </a>
    </li>
@endif

{{-- Menu TU untuk pegawai pemegang modul (Hak Akses). Akun role lama memakai sidebar role-nya sendiri. --}}
@if (Auth::user()->role->value === 'pegawai')
    @php $tu_user = Auth::user(); @endphp

    @if ($tu_user->hasAccess('kasir'))
        @include('layouts.backend.sidebar-kasir')
    @elseif ($tu_user->hasAccess('kasir-unit'))
        @include('layouts.backend.sidebar-kasir-unit')
    @endif

    @if ($tu_user->hasAccess('bendahara'))
        @include('layouts.backend.sidebar-bendahara')
    @endif

    @if ($tu_user->hasAccess('penanggung-jawab-tabungan'))
        @include('layouts.backend.sidebar-penanggung-jawab-tabungan')
    @endif

    @if ($tu_user->hasAccess('wali-kelas') && ! $tu_user->hasAccess('kasir'))
        @include('layouts.backend.sidebar-wali-kelas')
    @endif
@endif
