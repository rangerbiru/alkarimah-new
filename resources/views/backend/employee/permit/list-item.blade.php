@php
    $isOwnPermit = $permit->employee_id == Auth::user()?->employee?->id;
@endphp

<button type="button" data-bs-toggle="modal" data-bs-target="#permit-{{ $permit->id }}"
    class="d-flex align-items-center py-3 list-group-item w-100">
    <div class="me-3">
        <div class="bg-{{ $isOwnPermit ? 'success' : 'primary' }} rounded-circle p-2 d-flex align-items-center justify-content-center"
            style="width: 30px; height: 30px;">
            <i class="fas fa-box text-white"></i>
        </div>
    </div>

    <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-start">
                <h6>
                    {{ strtoupper(substr($permit->employee->name ?? '', 0, 3)) }} - {{ $permit->id }}
                </h6>
                <div class="text-{{ $isOwnPermit ? 'success' : 'primary' }} fw-bold mt-1">
                    {{ strtoupper($permit->permitType->permit_type ?? '-') }}
                </div>
            </div>
            <div class="text-end">
                <span class="badge {{ badgeClass($permit->status) }}">{{ strtoupper($permit->status) }}</span>
            </div>
        </div>
    </div>
</button>
