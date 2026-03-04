@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
<x-section-page-mobile
    :label="$title"
    :icon="$icon"
/>
@endsection

@section('content')
<div class="card card-tab card-student mb-3" data-id="{{ $student->encrypted_id }}">
    <div class="card-body p-2">
        <div class="d-flex mb-3">
            <div class="me-3">
                <img src="{{ $student->photo_url }}" style="width: 75px;" />
            </div>
            <div class="mt-1">
                <h5 class="mb-0 text-info">
                    {{ $student->name }}
                </h5>
                <b class="text-grey">{{ __('label.nis') }} : {{ $student->nis }}</b>

                <div class="mt-1">
                    <a href="{{ route('academic.student.edit', $student->encrypted_id) }}">
                        <span class="badge bg-secondary">UPDATE PROFILE <i class="fa-solid fa-angle-right"></i></span>
                    </a>
                </div>
            </div>
        </div>

        <div class="d-flex border-bottom pb-2">
            <div>{{ __('label.nik') }}</div>
            <div class="ms-auto">{{ (empty($student->nik)) ? '-' : $student->nik }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            <div>{{ __('label.gender') }}</div>
            <div class="ms-auto">{{ $student->gender_name }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            <div>{{ __('label.religion') }}</div>
            <div class="ms-auto">{{ $student->religion }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            <div>{{ __('label.birthdate') }}</div>
            <div class="ms-auto">{{ (empty($student->birthdate)) ? '-' : Common::dateFormat($student->birthdate, 'dd mmm yyyy') }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            <div>{{ __('label.birthplace') }}</div>
            <div class="ms-auto">{{ (empty($student->birthplace)) ? '-' : $student->birthplace }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            <div>{{ __('label.parent') }}</div>
            <div class="ms-auto">{{ $student->parent->name }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            <div>{{ __('label.child_ke') }}</div>
            <div class="ms-auto">{{ (empty($student->child)) ? '-' : $student->child }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            {{ __('label.address') }}<br />
            {{ (empty($student->address)) ? '-' : $student->address }}
        </div>

        <x-section-form
            icon="fa-solid fa-award"
            :label="__('label.academic')"
        />
        <div class="d-flex border-bottom pb-2">
            <div>{{ __('label.nisn') }}</div>
            <div class="ms-auto">{{ (empty($student->nisn)) ? '-' : $student->nisn }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            <div>{{ __('label.nis_local') }}</div>
            <div class="ms-auto">{{ (empty($student->nis_local)) ? '-' : $student->nis_local }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            <div>{{ __('label.student_card_number') }}</div>
            <div class="ms-auto">{{ (empty($student->card_number)) ? '-' : $student->card_number }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            <div>{{ __('label.level_education') }}</div>
            <div class="ms-auto">{{ strtoupper($student->class->level_education->value) }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            <div>{{ __('label.class') }}</div>
            <div class="ms-auto">{{ $student->class->name }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            <div>{{ __('label.asrama') }}</div>
            <div class="ms-auto">{{ (empty($student->id_asrama)) ? '-' : $student->asrama->name }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            <div>{{ __('label.halaqah') }}</div>
            <div class="ms-auto">{{ (empty($student->id_halaqah)) ? '-' : $student->halaqah->name }}</div>
        </div>
        <div class="d-flex border-bottom py-2">
            <div>{{ __('label.school_from') }}</div>
            <div class="ms-auto">{{ (empty($student->school_from)) ? '-' : $student->school_from }}</div>
        </div>

        <x-section-form
            icon="fa-solid fa-universal-access"
            :label="__('label.excul')"
        />

        @if (empty($student->exculs))
            <span class="text-muted">{{ __('label.not_participating') }}</span>
        @else
            <ol style="margin-left: -16px;">
                @foreach ($student->excul_list as $e)
                    <li class="pb-1">{{ $e->name }}</li>
                @endforeach
            </ol>
        @endif

        <hr />
        <a href="{{ route('academic.student.index') }}" class="btn btn-secondary">
            {{ __('label.close') }}
        </a>
    </div>
</div>
@endsection
