<h4 class="fw-medium mb-0">
    <i class="{{ $icon }}"></i> {{ $label }}
</h4>

<div class="ms-sm-1 ms-0 mt-2">
    {{ $breadcrumb_data === false ? Breadcrumbs::render($breadcrumb) : Breadcrumbs::render($breadcrumb, $breadcrumb_data) }}
</div>
