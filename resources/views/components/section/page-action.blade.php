<div class="row mb-3">
    <div class="col-sm-7 col-md-3">
        <div class="alert alert-outline-info">
            <div class="clearfix">
                <div class="float-end"><i class="{{ $icon }}" style="font-size: 16px;"></i></div>

                <b>{{ number_format($count, 0, '', '.') }}</b> {{ $label }}
            </div>
        </div>
    </div>
    <div class="col-sm-5 col-md-9">
        <div class="d-block d-sm-none mt-3"></div>

        @if ($filter)
            <div class="d-flex">
                <div>
                    <a href="{{ $createRoute }}" class="btn btn-primary label-btn">
                        <i class="bx bxs-plus-circle label-btn-icon me-2"></i>
                        {{ __('label.create') }}
                    </a>
                </div>
                <div class="ms-auto">
                    <div>
                        <div class="input-group" style="flex-wrap: nowrap;">
                            <span class="input-group-text"><i class="ti ti-filter-search"></i></span>
                            <x-form.select id="page-filter" :option="$filter_option" :data-placeholder="$filter_placeholder"
                                data-allow-clear="true" />
                        </div>
                    </div>
                </div>
            </div>
        @elseif (!empty($createRoute))
            @if (is_array($createRoute))
                @foreach ($createRoute as $r)
                    <a href="{{ $r['route'] }}" class="btn btn-{{ $r['color'] }} label-btn">
                        <i class="{{ $r['icon'] }} label-btn-icon me-2"></i>
                        {{ $r['label'] }}
                    </a>
                @endforeach
            @else
                <a href="{{ $createRoute }}" class="btn btn-primary label-btn">
                    <i class="bx bxs-plus-circle label-btn-icon me-2"></i>
                    {{ __('label.create') }}
                </a>
            @endif
        @endif
    </div>
</div>
