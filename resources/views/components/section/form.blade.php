<div {{ $attributes->merge(['class' => 'mt-4 mb-3 text-' . $color, 'style' => 'font-size: 13px;']) }}>
    <span class="pb-2" style="border-bottom: 1px solid #d7dee1;">
        @if (!empty($icon))
            <i class="{{ $icon }}"></i>&nbsp;
        @endif

        <b>{{ $label }}</b>
    </span>
</div>
