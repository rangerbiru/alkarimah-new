
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
            <form method="post" action="{{ route('user.profile.update') }}" class="form-block">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <x-form.input-text
                            label="{{ __('label.name') }}"
                            name="name"
                            :old="old('name', $user->name)"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6 col-md-6">
                        <x-form.input-group-mask
                            name="phone"
                            :label="__('label.phone_number')"
                            :old="old('phone', $user->phone)"
                            mask="handphone"
                            addon="<i class='bx bx-mobile'></i>"
                        />
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <x-form.input-group-mask
                            name="email"
                            :label="__('label.email')"
                            :old="old('email', $user->email)"
                            mask="email"
                            addon="<i class='bx bxs-envelope'></i>"
                        />
                    </div>
                </div>
                <div class="col-md-4">
                    <x-form.radio
                        name="gender"
                        :label="__('label.gender')"
                        :old="old('gender')"
                        :option="$genders"
                    />
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-form.input-text
                            label="{{ __('label.role') }}"
                            name="role"
                            :old="old('role', $user->role)"
                            disabled
                        />
                    </div>
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

    $(".handphone-mask").inputmask({ alias: "handphone" })
    $(".email-mask").inputmask({ alias: "email" })
})
</script>
@endpush
