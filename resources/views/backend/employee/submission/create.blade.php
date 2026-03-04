@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="employee/submission/create" />
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">

            <form method="post" action="{{ route('employee.submission.store') }}" class="form-block"
                enctype="multipart/form-data">
                <div class="card">
                    <div class="card-body">
                        @csrf
                        <input type="hidden" name="employee_id" id="employee_id" value="{{ $idEmployee }}">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-12">
                                <x-form.input-text name="name_activity" :label="__('label.name_activity')" :old="old('name_activity')" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                @php
                                    $activityType = [
                                        'service' => 'Jasa',
                                        'item' => 'Pengadaan Barang',
                                        'fund' => 'Dana',
                                    ];
                                @endphp
                                <x-form.select id="activity_type" name="activity_type" :label="__('label.type')" :option="$activityType"
                                    :old="old('activity_type')" />
                            </div>
                        </div>

                        {{-- <div class="row">
                            <div class="col-md-12">
                                <label>{{ __('label.location') }}</label>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    @php
                                        $oldLocations = old('location', []);
                                    @endphp

                                    @foreach ($location as $value => $label)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="location[]"
                                                value="{{ $value }}" id="location-{{ $value }}"
                                                @if (in_array($value, $oldLocations)) checked @endif>
                                            <label class="form-check-label fs-11" for="location-{{ $value }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div> --}}

                        <div class="row">
                            <div class="col-md-12">
                                <x-form.select name="location[]" :label="__('label.location')" :option="$location" :old="old('location')"
                                    required multiple />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <x-form.text-area name="description" :label="__('label.information')" :old="old('description')" />
                            </div>
                        </div>

                        <div
                            class="d-flex justify-content-between align-items-center bg-success-subtle border border-dark p-2 rounded-2">
                            <h6 class="mb-0">Total Perkiraan Harga</h6>
                            <h6 class="mb-0">Rp. <span id="total">0</span></h6>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="button"
                                    class="btn btn-primary w-100 d-flex gap-2 align-items-center justify-content-center"
                                    data-bs-toggle="modal" data-bs-target="#modalItem">
                                    <i class='bx bxs-package' style="font-size: 18px !important;"></i>
                                    {{ __('label.add_item') }}
                                </button>
                            </div>

                            <div class="modal fade" id="modalItem" tabindex="-1" aria-labelledby="exampleModalLabel"
                                aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">{{ __('label.list_item') }}
                                            </h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="pb-3 d-flex align-items-center">
                                                <div class="input-group">
                                                    <input type="text" id="search-item" class="form-control"
                                                        placeholder="Cari barang...">

                                                </div>

                                                <div class="ms-3">
                                                    <div class="d-flex justify-content-between align-items-center ">
                                                        <a href="{{ route('employee.submission.item.create') }}"
                                                            class="btn btn-primary label-btn">
                                                            {{ __('label.add') }}
                                                            <i class="fe fe-plus label-btn-icon me-2"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            @foreach ($items as $item)
                                                <div class="d-flex align-items-center py-3 list-group-item item-row"
                                                    data-name="{{ strtolower($item->name) }}">
                                                    <div class="me-3">
                                                        @if ($item->photo)
                                                            <div class="bg-success relative rounded-circle p-1">
                                                                <img src="{{ asset('storage/items/' . $item->photo) }}"
                                                                    alt="{{ $item->name }}"
                                                                    class="img-fluid object-fit-cover item-image"
                                                                    style="width: 60px; height: 60px; border-radius: 50%;">
                                                            </div>
                                                        @else
                                                            <div class="bg-success rounded-circle p-2 d-flex align-items-center justify-content-center"
                                                                style="width: 60px; height: 60px;">
                                                                <i class="fas fa-box-open text-white"
                                                                    style="font-size: 32px;"></i>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="flex-grow-1">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="text-capitalize">{{ $item->name }}</h6>
                                                                <div class="text-success fw-bold mt-1">
                                                                    Rp. {{ number_format($item->price, 0, ',', '.') }} /
                                                                    {{ $item->unit }}
                                                                </div>
                                                            </div>

                                                            <div class="text-end">
                                                                <button type="button" class="btn btn-primary btn-sm">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Hidden input untuk kirim data item yang dipilih --}}
                        <input type="hidden" name="selected_items" id="selected-items-json"
                            value="{{ old('selected_items', '[]') }}">
                    </div>
                </div>




                <div class="card mt-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0">{{ __('label.selected_items') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="selected-items-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Barang</th>
                                        <th>Satuan</th>
                                        <th>Harga</th>
                                        <th>Jumlah</th>
                                        <th>Subtotal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="selected-items-body">

                                </tbody>
                            </table>
                        </div>

                        @if ($errors->has('items'))
                            <div class="text-danger mt-2">{{ $errors->first('items') }}</div>
                        @endif

                        <x-form.button-submit :cancel-route="route('employee.submission.index')" />

                    </div>
                </div>

            </form>

            {{-- Hidden input untuk kirim data ke server --}}
            <input type="hidden" name="selected_items" id="selected-items-json"
                value="{{ old('selected_items', '[]') }}">
        </div>
    </div>

    <style>
        .icon-photo {
            font-size: 72px;
            color: #888
        }

        .preview-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .item-image {
            width: 52px;
            height: 52px;
            border-radius: 100%;
        }

        .item-show {
            display: flex !important;
        }

        .item-hide {
            display: none !important;
        }
    </style>

    <script>
        const error =
            "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"

        $(document).ready(function() {
            if (error != "") setNotifInfo(error)
        })
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-item');
            const itemRows = document.querySelectorAll('.item-row');

            searchInput.addEventListener('keyup', function() {
                const keyword = this.value.toLowerCase().trim();

                itemRows.forEach(row => {
                    const itemName = row.dataset.name;

                    row.classList.remove('item-show', 'item-hide');

                    if (itemName.includes(keyword)) {
                        row.classList.add('item-show');
                    } else {
                        row.classList.add('item-hide');
                    }
                });
            });
        });
    </script>


    @push('scripts')
        <script>
            // Simpan daftar item yang dipilih
            let selectedItems = @json(old('selected_items', []));

            // Render ulang tabel
            function renderSelectedItems() {
                const tbody = document.getElementById('selected-items-body');
                if (!tbody) return;

                tbody.innerHTML = '';

                let total = 0;

                selectedItems.forEach((item, index) => {
                    const quantity = item.quantity || 1;
                    const price = parseFloat(item.price) || 0;
                    const subtotal = quantity * price;
                    total += subtotal;

                    const row = `
            <tr>
                <td>${index + 1}</td>
                <td>${item.name || ''}</td>
                <td>${item.unit || ''}</td>
                <td>Rp ${price.toLocaleString('id-ID')}</td>
                <td>
                    <input type="number" 
                           class="form-control form-control-sm item-quantity" 
                           value="${quantity}" 
                           data-index="${index}"
                           min="1">
                </td>
                <td>Rp ${subtotal.toLocaleString('id-ID')}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-item" data-index="${index}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
                    tbody.innerHTML += row;
                });

                // ✅ Update total DI LUAR loop → selalu dijalankan
                const totalElement = document.getElementById('total');
                if (totalElement) {
                    totalElement.textContent = total.toLocaleString('id-ID');
                }

                // Simpan ke input hidden
                const hiddenInput = document.getElementById('selected-items-json');
                if (hiddenInput) {
                    hiddenInput.value = JSON.stringify(selectedItems);
                }
            }

            document.querySelectorAll('#modalItem .btn-primary.btn-sm').forEach((button, index) => {
                button.addEventListener('click', function() {
                    const item = @json($items)[index];
                    if (!item) return;

                    // Cek apakah sudah ada
                    const exists = selectedItems.find(i => i.id === item.id);
                    if (exists) {
                        alert('Barang ini sudah dipilih.');
                        return;
                    }

                    selectedItems.push({
                        id: item.id,
                        name: item.name,
                        unit: item.unit,
                        price: parseFloat(item.price),
                        quantity: 1
                    });

                    renderSelectedItems();
                });
            });

            // Event: hapus item
            document.getElementById('selected-items-table').addEventListener('click', function(e) {
                if (e.target.closest('.remove-item')) {
                    const index = e.target.closest('.remove-item').dataset.index;
                    selectedItems.splice(index, 1);
                    renderSelectedItems();
                }
            });

            // Event: ubah jumlah
            document.getElementById('selected-items-table').addEventListener('change', function(e) {
                if (e.target.classList.contains('item-quantity')) {
                    const index = e.target.dataset.index;
                    const newQty = parseInt(e.target.value) || 1;
                    selectedItems[index].quantity = newQty;
                    renderSelectedItems(); // Re-render untuk update subtotal
                }
            });

            // Render awal
            renderSelectedItems();
        </script>
    @endpush
@endsection
