@extends('layouts.backend.index')

@section('title', $title)
@section('header')
    <x-section-page :label="$title" :icon="$icon" breadcrumb="hr/inventory-item/create" />
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('hr.inventory-item.store') }}" class="form-block"
                enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <x-form.input-text name="no_nota" id="no_nota" :label="__('No Nota')" :value="old('no_nota')" required />
                        <input type="hidden" name="asset_id" id="asset_id" readonly>
                        <input type="hidden" name="unique_id" id="unique_id" readonly>
                    </div>
                    <div class="col-md-6">
                        <x-form.input-text name="inventory_code" id="inventory_code" :label="__('Kode Inventaris')" :value="old('inventory_code')"
                            required readonly />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="item_select">{{ __('Nama Aset') }}</label>
                            <select id="item_select" class="form-control select2 @error('name') is-invalid @enderror"
                                required>
                                <option value="">{{ __('Pilih Nama Aset') }}</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}"
                                        data-category-code="{{ $item->category->code ?? '' }}"
                                        {{ old('name') == $item->id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <input type="hidden" name="name" hidden readonly id="name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <x-form.input-text name="category" id="category" readonly :label="__('Kategori Aset')" :value="old('category')"
                            required readonly />
                        <input type="hidden" name="category_id" id="category_id">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="location">{{ __('Lokasi') }}</label>
                            <select name="location" id="location"
                                class="form-control select2 @error('location') is-invalid @enderror" required>
                                <option value="">{{ __('Pilih Lokasi') }}</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}" data-code="{{ $loc->code }}"
                                        {{ old('location') == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }} ({{ $loc->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="unit">{{ __('Unit / Bagian') }}</label>
                            <select name="unit" id="unit"
                                class="form-control select2 @error('unit') is-invalid @enderror" required>
                                <option value="">{{ __('Pilih Unit') }}</option>
                                @if (old('location'))
                                    @foreach ($units->where('location_id', old('location')) as $unit)
                                        <option value="{{ $unit->unit }}">
                                            {{ $unit->unit }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <x-form.input-text name="brand" id="brand" readonly :label="__('Merk / Tipe')" :value="old('brand')" />
                    </div>
                    <div class="col-md-6">
                        <label for="name">{{ __('label.responsible_person') }}</label>
                        <select id="selected_responsible_person" class="form-select select2">
                            <option value="">-- Pilih Ustadz --</option>
                            @foreach ($employees as $ustadz)
                                <option value="{{ $ustadz->id }}">{{ $ustadz->name }}</option>
                            @endforeach
                        </select>

                        <input type="hidden" name="responsible_person" id="responsible_person" class="form-control"
                            readonly value="{{ old('responsible_person') }}" />
                    </div>

                </div>

                <div class="row">
                    <div class="col-md-12">
                        <x-form.text-area name="specification" id="description" :label="__('Spesifikasi')" :value="old('specification')"
                            readonly rows="3" />
                    </div>
                </div>

                <x-section-form :label="__('label.inventory_detail')" icon="ti ti-package" />

                <div class="row">
                    <div class="col-md-6">
                        <x-form.input-text name="serial_number" :label="__('Nomor Seri')" :value="old('serial_number')" />
                    </div>
                    <div class="col-md-6">
                        <label for="Date">{{ __('Tanggal Perolehan') }}</label>
                        <input type="date" name="acquisition_date" id="acquisition_date" class="form-control"
                            value="{{ old('acquisition_date') }}" />
                    </div>

                </div>

                <div class="row">
                    <div class="col-md-6">
                        @php
                            $source = [
                                'bos' => 'BOS',
                                'yayasan' => 'Yayasan',
                            ];
                        @endphp
                        <x-form.select name="source_funding" :option="$source" :label="__('Sumber Dana')" :value="old('source_funding')" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input-text type="number" id="acquisition_price" name="acquisition_price" :label="__('Harga Perolehan')"
                            :value="old('acquisition_price')" step="0.01" id="acquisition_price" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <x-form.input-text type="number" name="quantity" :label="__('Jumlah')" :value="old('quantity', 1)"
                            min="1" id="quantity" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input-text type="number" name="total_acquisition_value" :label="__('Nilai Perolehan Total')"
                            :value="old('total_acquisition_value')" step="0.01" id="total_acquisition_value" readonly />
                    </div>
                    <div class="col-md-4">
                        <x-form.input-text type="number" name="residual_value" :label="__('Nilai Residu')" :value="old('residual_value')"
                            step="0.01" id="residual_value" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <x-form.input-text type="number" name="useful_life_years" :label="__('Masa Manfaat (Tahun)')" :value="old('useful_life_years')"
                            min="0" id="useful_life_years" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input-text name="depreciation_method" :label="__('Metode Penyusutan')" :value="old('depreciation_method')"
                            id="depreciation_method" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input-text name="used_until_date" readonly :label="__('Bulan Terpakai (s.d Tgl Laporan)')" :value="old('used_until_date')"
                            id="used_until_date" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <x-form.input-text type="number" name="depreciation_amount_per_year" :label="__('Penyusutan / Tahun')"
                            :value="old('depreciation_amount_per_year')" step="0.01" id="depreciation_amount_per_year" readonly />
                    </div>
                    <div class="col-md-4">
                        <x-form.input-text type="number" name="depreciation_amount_per_month" :label="__('Penyusutan / Bulan')"
                            :value="old('depreciation_amount_per_month')" step="0.01" id="depreciation_amount_per_month" readonly />
                    </div>
                    <div class="col-md-4">
                        <x-form.input-text type="number" name="accumulated_depreciation" :label="__('Akumulasi Penyusutan')"
                            :value="old('accumulated_depreciation')" step="0.01" id="accumulated_depreciation" readonly />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <x-form.input-text type="number" name="book_value" :label="__('Nilai Buku')" :value="old('book_value')"
                            step="0.01" id="book_value" readonly />
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="documents">{{ __('Dokumen') }}</label>
                        <input type="file" name="documents[]" id="documents" class="form-control" multiple
                            accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
                        <small class="text-muted">Pilih foto, nota, atau dokumen lainnya (maks 5 file, 10MB per
                            file)</small>

                        <!-- Preview Dokumen -->
                        <div id="document-preview" class="mt-2 d-none">
                            <h6>Preview:</h6>
                            <div class="row" id="preview-container"></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        @php
                            $conditionOptions = [
                                'Baik' => 'Baik',
                                'Rusak' => 'Rusak',
                                'Perlu Perbaikan' => 'Perlu Perbaikan',
                                'Lainnya' => 'Lainnya',
                            ];
                        @endphp
                        <x-form.select name="condition" :label="__('Kondisi')" :option="$conditionOptions" :value="old('condition')" />
                    </div>
                    <div class="col-md-6">
                        @php
                            $statusOptions = [
                                'Aktif' => 'Aktif',
                                'Tidak Aktif' => 'Tidak Aktif',
                            ];
                        @endphp
                        <x-form.select name="status" :label="__('Status')" :option="$statusOptions" :value="old('status', 'Aktif')" required />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <x-form.text-area name="description" :label="__('Keterangan')" :value="old('description')" rows="3" />
                    </div>
                </div>

                <x-form.button-submit :cancel-route="route('hr.inventory-item.index')" />
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        const error =
            "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"

        $(document).ready(function() {
            if (error != "")
                setNotifInfo(error)
        })
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ========== Data untuk auto-fill ==========
            const itemsData = @json($itemsData);
            const unitsData = @json($unitsData);

            // ========== Inisialisasi Select2 ==========
            $('#item_select').select2({
                placeholder: 'Pilih Nama Aset',
                allowClear: true,
                width: '100%'
            });

            $('#location').select2({
                placeholder: 'Pilih Lokasi',
                allowClear: true,
                width: '100%'
            });

            $('#unit').select2({
                placeholder: 'Pilih Unit',
                allowClear: true,
                width: '100%'
            });

            $('#selected_responsible_person').select2({
                placeholder: 'Pilih Nama Pegawai',
                allowClear: true
            });

            $('#selected_responsible_person').on('change', function() {
                $('#responsible_person').val($(this).val());
            });

            // ========== Event: Item Select Change ==========
            $('#item_select').on('change', function() {
                const selectedItemId = $(this).val();

                if (selectedItemId) {
                    const selectedItem = itemsData.find(item => item.id == selectedItemId);

                    if (selectedItem) {
                        $('#category').val(selectedItem.category_name || '');
                        $('#category_id').val(selectedItem.category_id || '');
                        $('#asset_id').val(selectedItem.id || '');
                        $('#brand').val(selectedItem.merk || '');
                        $('#description').val(selectedItem.description || '');
                        $('#acquisition_price').val(selectedItem.price || '');
                        $('#name').val(selectedItem.name || '');
                        const formattedSequence = String(selectedItem.next_sequence).padStart(3, '0');
                        $('#unique_id').val(formattedSequence);
                    }
                } else {
                    $('#category').val('');
                    $('#asset_id').val('');
                    $('#brand').val('');
                    $('#description').val('');
                    $('#acquisition_price').val('');
                    $('#name').val('');
                }
            });

            // Trigger jika ada old value
            if ($('#item_select').val()) {
                $('#item_select').trigger('change');
            }

            $('#location').on('change', function() {
                const locationId = $(this).val();
                const unitSelect = $('#unit');

                unitSelect.empty().trigger('change');
                unitSelect.append(new Option('Pilih Unit', '', false, false)).trigger('change');

                if (locationId) {
                    const filteredUnits = unitsData.filter(unit => unit.location_id == locationId);

                    if (filteredUnits.length > 0) {
                        filteredUnits.forEach(unit => {
                            const option = new Option(unit.unit, unit.unit, false, false);
                            unitSelect.append(option);
                        });
                        unitSelect.trigger('change');
                    }
                }
            });

            if ($('#location').val()) {
                $('#location').trigger('change');

                // Set selected unit jika ada
                const selectedUnitId = '{{ old('unit') }}';
                if (selectedUnitId) {
                    $('#unit').val(selectedUnitId).trigger('change');
                }
            }

            // ========== Kalkulasi Depresiasi ==========
            const acquisitionPrice = document.getElementById('acquisition_price');
            const quantity = document.getElementById('quantity');
            const totalAcquisitionValue = document.getElementById('total_acquisition_value');

            function calculateTotalValue() {
                const price = parseFloat(acquisitionPrice?.value) || 0;
                const qty = parseInt(quantity?.value) || 1;
                if (totalAcquisitionValue) {
                    totalAcquisitionValue.value = (price * qty).toFixed(0);
                }
                calculateDepreciation();
            }

            acquisitionPrice?.addEventListener('input', calculateTotalValue);
            quantity?.addEventListener('input', calculateTotalValue);

            // ========== Preview Dokumen ==========
            const documentsInput = document.getElementById('documents');
            const previewContainer = document.getElementById('preview-container');
            const previewSection = document.getElementById('document-preview');

            documentsInput?.addEventListener('change', function(e) {
                previewContainer.innerHTML = '';
                if (e.target.files.length > 0) {
                    previewSection.classList.remove('d-none');
                    Array.from(e.target.files).forEach(file => {
                        const div = document.createElement('div');
                        div.className = 'col-md-3 mb-2';

                        if (file.type.startsWith('image/')) {
                            const img = document.createElement('img');
                            img.src = URL.createObjectURL(file);
                            img.className = 'img-thumbnail w-100';
                            img.alt = file.name;
                            div.appendChild(img);
                        } else {
                            const icon = document.createElement('div');
                            icon.className = 'bg-light p-2 text-center rounded small';
                            icon.innerHTML =
                                `<i class="fas fa-file-alt fa-2x text-secondary"></i><br>${file.name}`;
                            div.appendChild(icon);
                        }
                        previewContainer.appendChild(div);
                    });
                } else {
                    previewSection.classList.add('d-none');
                }
            });
        });

        function generateInventoryCode() {
            const locationSelect = $('#location');
            const itemSelect = $('#item_select');
            const inventoryCodeInput = $('#inventory_code');
            const noNota = $('#no_nota').val();

            const locationCode = locationSelect.find(':selected').data('code');
            const categoryCode = itemSelect.find(':selected').data('category-code');

            if (!locationCode || !categoryCode) {
                inventoryCodeInput.val('');
                return;
            }

            // Ambil nilai dari unique_id (yang sudah diformat 001, 002, dll)
            const sequenceNumber = $('#unique_id').val() || '001';
            const noNotaDisplay = noNota ? noNota : '.....';

            const inventoryCode = `${locationCode}/${categoryCode}/${noNotaDisplay}/${sequenceNumber}`;
            inventoryCodeInput.val(inventoryCode);
        }

        // Update event listeners
        $('#location').on('change', generateInventoryCode);
        $('#item_select').on('change', function() {
            setTimeout(generateInventoryCode, 10);
        });
        $('#no_nota').on('input', generateInventoryCode);

        $(document).ready(function() {
            if ($('#location').val() && $('#item_select').val()) {
                generateInventoryCode();
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const acquisitionInput = document.querySelector('[name="acquisition_date"]');
            const usedUntilInput = document.getElementById('used_until_date');

            if (!acquisitionInput) return;

            function calculateMonths() {
                if (!acquisitionInput.value) {
                    usedUntilInput.value = '';
                    return;
                }

                const acquisitionDate = new Date(acquisitionInput.value);
                const today = new Date();

                let months =
                    (today.getFullYear() - acquisitionDate.getFullYear()) * 12 +
                    (today.getMonth() - acquisitionDate.getMonth());

                if (today.getDate() < acquisitionDate.getDate()) {
                    months--;
                }

                usedUntilInput.value = `${Math.max(months, 0)} Bulan`;
            }

            acquisitionInput.addEventListener('input', calculateMonths);
            acquisitionInput.addEventListener('change', calculateMonths);

            calculateMonths();
        });
    </script>
@endpush
