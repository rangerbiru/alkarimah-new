@php
$set_waiting = ($render == 'waiting') ? ' active' : '';
$set_accepted = ($render == 'accepted') ? ' active' : '';
$set_rejected = ($render == 'rejected') ? ' active' : '';
@endphp

<nav class="navbar navbar-page navbar-expand-lg">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a href="{{ route('finance.transaction.cash', 'waiting') }}" class="nav-link{{ $set_waiting }}">
                        <i class="fa-solid fa-clipboard-list"></i> &nbsp;Menunggu Persetujuan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('finance.transaction.cash', 'accepted') }}" class="nav-link{{ $set_accepted }}">
                        <i class="fa-solid fa-clipboard-check"></i> &nbsp;Diterima
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('finance.transaction.cash', 'rejected') }}" class="nav-link{{ $set_rejected }}">
                        <i class="fa-solid fa-times-circle"></i> &nbsp;Ditolak
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
