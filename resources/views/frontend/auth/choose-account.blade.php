@extends('layouts.auth.index')

@section('content')
<p class="h4 fw-semibold mb-2">{{ __('label.choose_account') }}</p>
<p class="mb-3 text-muted op-7 fw-normal">{{ __('string.choose_account_to_login') }}</p>

<form id="form-choose-account" method="post" action="{{ route('auth.store.choose-account') }}" class="form-block mb-4">
    @csrf

    @isset($errors->all()[0])
        <div class="alert alert-danger">
            <i class="fa-solid fa-times-circle"></i>&nbsp; {{ $errors->all()[0] }}
        </div>
    @endisset

    @foreach ($accounts as $account)
        <label class="d-flex align-items-start border rounded p-3 mb-2" for="account-{{ $loop->index }}" style="cursor: pointer">
            <input class="form-check-input mt-1 me-3 flex-shrink-0" type="radio" name="account" id="account-{{ $loop->index }}" value="{{ $account->id }}" @checked($loop->first)>
            <span class="d-block">
                <span class="fw-semibold d-block">{{ $account->name }}</span>
                <span class="badge bg-light text-dark">{{ $account->role }}</span>

                @if (! empty($account->children))
                    <small class="text-muted d-block mt-1">{{ __('label.children') }}: {{ implode(', ', $account->children) }}</small>
                @endif
            </span>
        </label>
    @endforeach

    <div class="d-grid mt-4">
        <button type="submit" class="btn btn-lg btn-primary w-100">
            {{ strtoupper(__('label.login')) }}
        </button>
    </div>

    <div class="mt-3 text-center">
        <a href="{{ route('base') }}" class="text-primary">{{ __('string.choose_account_back') }}</a>
    </div>
</form>
@endsection
