@php
    use App\Constants\Modules;
@endphp

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

    {{-- Menu umum sesuai modul Hak Akses: Kelola Akademik / Kepegawaian / Pengadaan Barang. --}}
    @php
        $user_rights = $tu_user->moduleIds();
        $general_sections = [];
        if (in_array(Modules::AdminAcademic, $user_rights)) {
            $general_sections[] = 'academic';
        }
        if (in_array(Modules::AdminHumanResource, $user_rights)) {
            $general_sections[] = 'hr';
        }
        if (in_array(Modules::AdminInventory, $user_rights)) {
            $general_sections[] = 'inventory';
        }
    @endphp

    @if ($general_sections)
        @include('layouts.backend.sidebar-general', ['sections' => $general_sections, 'headings' => true])
    @endif
@endif
