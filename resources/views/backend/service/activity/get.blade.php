@if ($activity->count() == 0)
    {{ __('string.there_is_no_activity_yet') }}
@else
    @foreach ($activity as $a)
        <div class="card card-history mb-2">
            <div class="card-body p-2">
                <div class="d-flex">
                    <div>
                        <div class="icon">
                            <i class="{{ $a->icon }}"></i>
                        </div>
                    </div>
                    <div class="text">
                        <b>{{ $a->title }}</b><br />
                        <small>
                            "{{ $a->message }}"
                        </small>
                        <div class="text-muted mt-1">
                            <small>{{ Common::dateFormat($a->created_at, 'dd mmm yyyy') . ', ' . date('H:i', strtotime($a->created_at)) . ' WIB' }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
