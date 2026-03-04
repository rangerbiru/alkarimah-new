@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/payroll/setup/edit"
    :breadcrumb-data="$employee->encrypted_id"
/>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <img src={{ $employee->photo }} class="img-fluid" style="width: 200px;">
                <h5 class="card-title mt-3">{{$employee->name}}</h5>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <table class="table-padding">
                        <tr>
                            <td>{{ __('label.nip') }}</td>
                            <td class="divide">:</td>
                            <td>{{ (empty($employee->nip)) ? '-' : $employee->nip }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('label.nik') }}</td>
                            <td class="divide">:</td>
                            <td>{{ (empty($employee->nik)) ? '-' : $employee->nik }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('label.phone_number') }}</td>
                            <td class="divide">:</td>
                            <td>{{ Common::phoneFormat($employee->phone) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('label.marital_status') }}</td>
                            <td class="divide">:</td>
                            <td>{{ $employee->marital_status_name }}</td>
                        </tr>
                    </table>
                </li>
            </ul>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="post" action="{{ route('finance.payroll.update.setup', $employee->encrypted_id) }}" class="form-block">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-sm-6 col-md-5">
                            <x-form.input-group-mask
                                name="salary"
                                :label="__('label.basic_salary')"
                                :old="$employee->salary"
                                mask="nominal"
                                addon="Rp"
                            />
                        </div>
                    </div>

                    <x-section-form
                        icon="bx bx-credit-card-alt"
                        :label="__('label.structural_allowance')"
                    />
                    <div class="table-responsive" id="table-allowance-structural">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('label.allowance') }}</th>
                                    <th style="width: 250px;">{{ __('label.nominal') }}</th>
                                    <th style="width: 40px;">#</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!empty($employee->salary_allowance_detail))
                                    @foreach ($employee->salary_allowance_detail->structural as $s)
                                        <tr>
                                            <td>
                                                <x-form.select
                                                    name="structural[]"
                                                    :option="$allowances->structural"
                                                    :old="$s->id"
                                                />
                                            </td>
                                            <td>
                                                <x-form.input-group-mask
                                                    name="structural_nominal[]"
                                                    mask="nominal"
                                                    addon="Rp"
                                                    :old="$s->nominal"
                                                />
                                            </td>
                                            <td>
                                                <a href="javascript:void(0)" class="btn-delete text-danger">
                                                    <i class="fa-solid fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3">
                                        <a href="javascript:void(0)" class="btn-add text-muted">
                                            <small><i class="fa-solid fa-plus"></i> &nbsp;{{ __('label.add_new') }}</small>
                                        </a>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <x-section-form
                        icon="bx bx-credit-card-alt"
                        :label="__('label.liability_allowance')"
                    />
                    <div class="table-responsive" id="table-allowance-liability">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('label.allowance') }}</th>
                                    <th style="width: 250px;">{{ __('label.nominal') }}</th>
                                    <th style="width: 40px;">#</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!empty($employee->salary_allowance_detail))
                                    @foreach ($employee->salary_allowance_detail->liability as $s)
                                        <tr>
                                            <td>
                                                <x-form.select
                                                    name="liability[]"
                                                    :option="$allowances->liability"
                                                    :old="$s->id"
                                                />
                                            </td>
                                            <td>
                                                <x-form.input-group-mask
                                                    name="liability_nominal[]"
                                                    mask="nominal"
                                                    addon="Rp"
                                                    :old="$s->nominal"
                                                />
                                            </td>
                                            <td>
                                                <a href="javascript:void(0)" class="btn-delete text-danger">
                                                    <i class="fa-solid fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3">
                                        <a href="javascript:void(0)" class="btn-add text-muted">
                                            <small><i class="fa-solid fa-plus"></i> &nbsp;{{ __('label.add_new') }}</small>
                                        </a>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <x-section-form
                        icon="bx bx-credit-card-alt"
                        :label="__('label.performance_allowance')"
                    />
                    <div class="table-responsive" id="table-allowance-performance">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('label.allowance') }}</th>
                                    <th style="width: 250px;">{{ __('label.nominal') }}</th>
                                    <th style="width: 40px;">#</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!empty($employee->salary_allowance_detail))
                                    @foreach ($employee->salary_allowance_detail->performance as $s)
                                        <tr>
                                            <td>
                                                <x-form.select
                                                    name="performance[]"
                                                    :option="$allowances->performance"
                                                    :old="$s->id"
                                                />
                                            </td>
                                            <td>
                                                <x-form.input-group-mask
                                                    name="performance_nominal[]"
                                                    mask="nominal"
                                                    addon="Rp"
                                                    :old="$s->nominal"
                                                />
                                            </td>
                                            <td>
                                                <a href="javascript:void(0)" class="btn-delete text-danger">
                                                    <i class="fa-solid fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3">
                                        <a href="javascript:void(0)" class="btn-add text-muted">
                                            <small><i class="fa-solid fa-plus"></i> &nbsp;{{ __('label.add_new') }}</small>
                                        </a>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <x-form.button-submit :cancel-route="route('finance.payroll.setup')" />
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

    $(".nominal-mask").inputmask({ alias: "nominal" })

    $("#table-allowance-structural .btn-add").click(function() {
        $("#table-allowance-structural tbody").append(`<tr>
                <td>
                    <x-form.select
                        name="structural[]"
                        :option="$allowances->structural"
                    />
                </td>
                <td>
                    <x-form.input-group-mask
                        name="structural_nominal[]"
                        mask="nominal"
                        addon="Rp"
                    />
                </td>
                <td>
                    <a href="javascript:void(0)" class="btn-delete text-danger">
                        <i class="fa-solid fa-trash-alt"></i>
                    </a>
                </td>
            </tr>`)

        $(".set-select2").select2({
            placeholder: "{{ __('label.choose') }}",
            allowClear: false,
            width: "100%"
        })

        $(".nominal-mask").inputmask({ alias: "nominal" })
    })

    $("#table-allowance-liability .btn-add").click(function() {
        $("#table-allowance-liability tbody").append(`<tr>
                <td>
                    <x-form.select
                        name="liability[]"
                        :option="$allowances->liability"
                    />
                </td>
                <td>
                    <x-form.input-group-mask
                        name="liability_nominal[]"
                        mask="nominal"
                        addon="Rp"
                    />
                </td>
                <td>
                    <a href="javascript:void(0)" class="btn-delete text-danger">
                        <i class="fa-solid fa-trash-alt"></i>
                    </a>
                </td>
            </tr>`)

        $(".set-select2").select2({
            placeholder: "{{ __('label.choose') }}",
            allowClear: false,
            width: "100%"
        })

        $(".nominal-mask").inputmask({ alias: "nominal" })
    })

    $("#table-allowance-performance .btn-add").click(function() {
        $("#table-allowance-performance tbody").append(`<tr>
                <td>
                    <x-form.select
                        name="performance[]"
                        :option="$allowances->performance"
                    />
                </td>
                <td>
                    <x-form.input-group-mask
                        name="performance_nominal[]"
                        mask="nominal"
                        addon="Rp"
                    />
                </td>
                <td>
                    <a href="javascript:void(0)" class="btn-delete text-danger">
                        <i class="fa-solid fa-trash-alt"></i>
                    </a>
                </td>
            </tr>`)

        $(".set-select2").select2({
            placeholder: "{{ __('label.choose') }}",
            allowClear: false,
            width: "100%"
        })

        $(".nominal-mask").inputmask({ alias: "nominal" })
    })

    $("#table-allowance-structural").on("click", ".btn-delete", function() {
        $(this).closest("tr").remove()
    })

    $("#table-allowance-liability").on("click", ".btn-delete", function() {
        $(this).closest("tr").remove()
    })

    $("#table-allowance-performance").on("click", ".btn-delete", function() {
        $(this).closest("tr").remove()
    })
})
</script>
@endpush
