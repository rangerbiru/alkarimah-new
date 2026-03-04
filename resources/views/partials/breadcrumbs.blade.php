@unless ($breadcrumbs->isEmpty())
    <nav>
        <ol class="breadcrumb mb-0">
            @foreach ($breadcrumbs as $index => $breadcrumb)
                @if (!is_null($breadcrumb->url) && !$loop->last)
                    <li class="breadcrumb-item">
                        <a href="{{ $breadcrumb->url }}">
                            {!! ($index == 0) ? '<i data-feather="airplay"></i> &nbsp;' . __('label.dashboard') : $breadcrumb->title !!}
                        </a>
                    </li>
                @elseif ($breadcrumb->title == 'Dashboard')
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard.index') }}">
                            <i data-feather="airplay"></i> &nbsp;{{ __('label.dashboard') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Overview</li>
                @else
                    <li class="breadcrumb-item active">{{ $breadcrumb->title }}</li>
                @endif

            @endforeach
        </ol>
    </nav>
@endunless
