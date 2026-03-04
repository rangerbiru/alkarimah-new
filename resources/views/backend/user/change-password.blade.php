@extends((Auth::user()->isOrangTua or Auth::user()->isPegawai) ? 'layouts.mobile.index' : 'layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
/>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <img src={{ $user->photo }} class="img-fluid" style="width: 200px;">
                <h5 class="card-title mt-3">{{$user->name}}</h5>
                <p class="card-text">{{$user->email}}</p>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <i class="bx bx-calendar"></i> Terakhir Login : {{ \Carbon\Carbon::parse($user->lastlogin_at)->format('d M Y H:i') }}
                </li>
            </ul>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
            <form method="post" action="{{ route('user.password.update') }}" class="form-block">
                @csrf
                @method('PUT')
                <div class="col-sm-6 col-md-6">
                    <x-form.input-group
                        type="password"
                        name="old_password"
                        :label="__('label.old_password')"
                        :old="old('old_password', $user->old_password)"
                        addon="<i class='bx bxs-lock-alt'></i>"
                        autocomplete="new-password"
                    />
                </div>
                <div class="col-sm-6 col-md-6">
                    <x-form.input-group
                        type="password"
                        name="new_password"
                        :label="__('label.new_password')"
                        :old="old('new_password', $user->new_password)"
                        addon="<i class='bx bxs-lock-alt'></i>"
                        autocomplete="new-password"
                    />
                </div>
                <div class="col-sm-6 col-md-6">
                    <x-form.input-group
                        type="password"
                        name="new_password_confirmation"
                        :label="__('label.new_password_confirmation')"
                        :old="old('new_password_confirmation', $user->new_password_confirmation)"
                        addon="<i class='bx bxs-lock-alt'></i>"
                    />
                </div>
                <x-form.button-submit
                    :cancel-route="route('dashboard.index')"
                />
            </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const error = "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"

$(document).ready(function() {
    if (error != "")
        setNotifInfo(error)
})
</script>
@endpush
