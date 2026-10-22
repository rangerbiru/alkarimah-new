@if ($transaction->is_cancelled)
    <div class="alert alert-secondary mb-3" role="alert">
        <div class="fw-bold mb-1">{{ __('label.cancellation_reason') }}</div>
        <div style="white-space: pre-line;">{{ $transaction->cancellation_reason }}</div>
        <small class="d-block mt-2">{{ __('string.cancelled_parent_note') }}</small>
    </div>
@endif
