@extends('layouts.auth.index')

@section('content')
<p class="h4 fw-semibold mb-2">{{ __('label.choose_account') }}</p>
<p class="mb-3 text-muted op-7 fw-normal">{{ __('string.choose_account_to_reset') }}</p>

<form id="form-register" method="post" action="{{ route('auth.store.forgot-password.accounts') }}" class="form-block mb-4">
    @csrf

    @isset($errors->all()[0])
        <div class="alert alert-danger">
            <i class="fa-solid fa-times-circle"></i>&nbsp; {{ $errors->all()[0] }}
        </div>
    @endisset

    @foreach ($accounts as $account)
        <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="account" id="account-{{ $loop->index }}" value="{{ $account->id }}" @checked($loop->first && $accounts->count() === 1)>
            <label class="form-check-label" for="account-{{ $loop->index }}">
                {{ $account->name }} <span class="badge bg-light text-dark">{{ $account->role }}</span>
            </label>
        </div>
    @endforeach

    <div class="d-grid mt-4">
        <button type="submit" class="btn btn-lg btn-primary w-100">
            {{ strtoupper(__('label.next')) }}
        </button>
    </div>
</form>
@endsection
